<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\MovieNight;
use App\Models\MovieNightCandidate;
use App\Models\MovieNightInvite;
use App\Models\MovieNightVote;
use App\Models\Screening;
use Illuminate\Http\Request;

class MovieNightController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $nights = MovieNight::with(['host:id,name,username,avatar_url,role', 'winner:id,title,poster_url', 'invites.user:id,name,username,avatar_url,role'])
            ->withCount('candidates')
            ->where(function ($q) use ($me) {
                $q->where('host_id', $me->id)
                    ->orWhereHas('invites', fn ($i) => $i->where('user_id', $me->id));
            })
            ->orderBy('scheduled_at')
            ->get();

        return $nights->map(function ($night) use ($me) {
            $invite = $night->invites->firstWhere('user_id', $me->id);

            return array_merge($night->toArray(), [
                'is_host' => $night->host_id === $me->id,
                'my_status' => $night->host_id === $me->id ? 'host' : ($invite->status ?? null),
                'is_past' => $night->scheduled_at->lt(now()->subHours(6)),
            ]);
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'scheduled_at' => 'required|date|after:now',
            'location' => 'nullable|string|max:255',
            'meeting_link' => 'nullable|url|max:255',
            'user_ids' => 'nullable|array|max:30',
            'user_ids.*' => 'integer|exists:users,id',
            'movie_ids' => 'nullable|array|max:10',
            'movie_ids.*' => 'integer|exists:movies,id',
        ]);

        $me = $request->user();

        $night = new MovieNight;
        $night->host_id = $me->id;
        $night->title = $data['title'];
        $night->description = $data['description'] ?? null;
        $night->scheduled_at = $data['scheduled_at'];
        $night->location = $data['location'] ?? null;
        $night->meeting_link = $data['meeting_link'] ?? null;
        $night->status = 'voting';
        $night->save();

        foreach (array_unique($data['user_ids'] ?? []) as $userId) {
            if ($userId != $me->id) {
                MovieNightInvite::create(['movie_night_id' => $night->id, 'user_id' => $userId, 'status' => 'pending']);
            }
        }

        foreach (array_unique($data['movie_ids'] ?? []) as $movieId) {
            MovieNightCandidate::create(['movie_night_id' => $night->id, 'movie_id' => $movieId, 'added_by' => $me->id]);
        }

        return response()->json(['message' => 'Movie night created !', 'night' => $night], 201);
    }

    public function show(Request $request, $id)
    {
        $me = $request->user();
        $night = MovieNight::with([
            'host:id,name,username,avatar_url,role',
            'invites.user:id,name,username,avatar_url,role',
            'candidates.movie.genres',
            'candidates.addedBy:id,name,username',
            'winner.genres',
            'screening',
        ])->findOrFail($id);

        if (! $night->isParticipant($me)) {
            return response()->json(['message' => 'You are not invited to this movie night.'], 403);
        }

        $votes = MovieNightVote::where('movie_night_id', $night->id)
            ->selectRaw('candidate_id, COUNT(*) as total')->groupBy('candidate_id')->pluck('total', 'candidate_id');

        $candidates = $night->candidates->map(fn ($c) => array_merge($c->toArray(), [
            'votes_count' => (int) ($votes[$c->id] ?? 0),
        ]))->sortByDesc('votes_count')->values();

        $myInvite = $night->invites->firstWhere('user_id', $me->id);

        return array_merge($night->toArray(), [
            'candidates' => $candidates,
            'total_votes' => $votes->sum(),
            'my_vote' => MovieNightVote::where('movie_night_id', $night->id)->where('user_id', $me->id)->value('candidate_id'),
            'is_host' => $night->host_id === $me->id,
            'my_status' => $night->host_id === $me->id ? 'host' : $myInvite?->status,
            'room' => 'night-'.$night->id,
            'can_start' => $night->host_id === $me->id && $night->status === 'decided' && $night->winner?->has_video,
        ]);
    }

    public function update(Request $request, $id)
    {
        $night = $this->hostedNight($request, $id);

        $data = $request->validate([
            'title' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'scheduled_at' => 'required|date',
            'location' => 'nullable|string|max:255',
            'meeting_link' => 'nullable|url|max:255',
        ]);

        $night->fill($data)->save();

        return response()->json(['message' => 'Movie night updated !', 'night' => $night]);
    }

    public function destroy(Request $request, $id)
    {
        $this->hostedNight($request, $id)->delete();

        return response()->json(['message' => 'Movie night deleted.']);
    }

    public function invite(Request $request, $id)
    {
        $night = $this->hostedNight($request, $id);

        $data = $request->validate([
            'user_ids' => 'required|array|min:1|max:30',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        foreach ($data['user_ids'] as $userId) {
            if ($userId != $night->host_id) {
                MovieNightInvite::firstOrCreate(['movie_night_id' => $night->id, 'user_id' => $userId], ['status' => 'pending']);
            }
        }

        return response()->json(['message' => 'Invitations sent !']);
    }

    public function respond(Request $request, $id)
    {
        $data = $request->validate(['status' => 'required|in:accepted,declined']);

        $invite = MovieNightInvite::where('movie_night_id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        $invite->status = $data['status'];
        $invite->save();

        return response()->json(['message' => $data['status'] === 'accepted' ? 'See you there !' : 'Invitation declined.']);
    }

    public function addCandidate(Request $request, $id)
    {
        $night = MovieNight::findOrFail($id);
        $me = $request->user();
        $this->checkCanParticipate($night, $me->id);

        if ($night->status !== 'voting') {
            return response()->json(['message' => 'Voting is closed for this movie night.'], 422);
        }

        $data = $request->validate(['movie_id' => 'required|integer|exists:movies,id']);

        $candidate = MovieNightCandidate::firstOrCreate(
            ['movie_night_id' => $night->id, 'movie_id' => $data['movie_id']],
            ['added_by' => $me->id]
        );

        return response()->json(['message' => 'Movie added to the shortlist !', 'candidate' => $candidate->load('movie')], 201);
    }

    public function removeCandidate(Request $request, $id, $candidateId)
    {
        $night = MovieNight::findOrFail($id);
        $candidate = MovieNightCandidate::where('movie_night_id', $night->id)->findOrFail($candidateId);

        if ($night->host_id !== $request->user()->id && $candidate->added_by !== $request->user()->id) {
            return response()->json(['message' => 'Only the host or the person who added it can remove it.'], 403);
        }
        if ($night->status !== 'voting') {
            return response()->json(['message' => 'Voting is closed for this movie night.'], 422);
        }

        $candidate->delete();

        return response()->json(['message' => 'Removed from the shortlist.']);
    }

    public function vote(Request $request, $id)
    {
        $night = MovieNight::findOrFail($id);
        $me = $request->user();
        $this->checkCanParticipate($night, $me->id);

        if ($night->status !== 'voting') {
            return response()->json(['message' => 'Voting is closed for this movie night.'], 422);
        }

        $data = $request->validate(['candidate_id' => 'required|integer']);
        $candidate = MovieNightCandidate::where('movie_night_id', $night->id)->findOrFail($data['candidate_id']);

        MovieNightVote::updateOrCreate(
            ['movie_night_id' => $night->id, 'user_id' => $me->id],
            ['candidate_id' => $candidate->id]
        );

        return response()->json(['message' => 'Vote saved !']);
    }

    public function close(Request $request, $id)
    {
        $night = $this->hostedNight($request, $id);

        $winner = MovieNightCandidate::where('movie_night_id', $night->id)
            ->withCount('votes')
            ->orderByDesc('votes_count')
            ->orderBy('id')
            ->first();

        if (! $winner) {
            return response()->json(['message' => 'Add at least one movie to the shortlist first.'], 422);
        }

        $night->winner_movie_id = $winner->movie_id;
        $night->status = 'decided';
        $night->save();

        return response()->json(['message' => 'The winner is '.$winner->movie->title.' !', 'night' => $night->load('winner')]);
    }

    public function reopen(Request $request, $id)
    {
        $night = $this->hostedNight($request, $id);
        $night->status = 'voting';
        $night->winner_movie_id = null;
        $night->save();
        $night->screening()?->delete();

        return response()->json(['message' => 'Voting is open again.']);
    }

    public function start(Request $request, $id)
    {
        $night = $this->hostedNight($request, $id);
        $night->load('winner');

        if ($night->status !== 'decided' || ! $night->winner) {
            return response()->json(['message' => 'Pick the winning movie first.'], 422);
        }
        if (! $night->winner->has_video) {
            return response()->json(['message' => 'This movie has no video on Reelhouse, watch it together using the meeting link.'], 422);
        }

        Screening::where('movie_night_id', $night->id)->delete();

        $screening = Screening::create([
            'movie_id' => $night->winner->id,
            'title' => $night->title,
            'starts_at' => now()->addSeconds(10),
            'created_by' => $request->user()->id,
            'movie_night_id' => $night->id,
        ]);

        return response()->json(['message' => 'The screening starts in 10 seconds !', 'screening' => $screening], 201);
    }

    private function hostedNight(Request $request, $id): MovieNight
    {
        $night = MovieNight::findOrFail($id);
        abort_if($night->host_id !== $request->user()->id, 403, 'Only the host can do this.');

        return $night;
    }

    private function checkCanParticipate(MovieNight $night, int $userId): void
    {
        if ($night->host_id === $userId) {
            return;
        }
        $invite = MovieNightInvite::where('movie_night_id', $night->id)->where('user_id', $userId)->first();
        abort_if(! $invite || $invite->status === 'declined', 403, 'You need to accept the invitation first.');
    }
}
