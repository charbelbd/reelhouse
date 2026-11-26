<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Movie;
use App\Models\Review;
use App\Services\SentimentClient;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, $movieId)
    {
        $movie = Movie::findOrFail($movieId);

        $reviews = $movie->reviews()
            ->with('user:id,name,username,avatar_url,role,show_reviews')
            ->where('is_hidden', false)
            ->when($request->filled('sentiment'), fn ($q) => $q->where('sentiment', $request->sentiment))
            ->latest()
            ->paginate(10);

        $stats = $movie->reviews()->where('is_hidden', false)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')->first();

        $breakdown = $movie->reviews()->where('is_hidden', false)
            ->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');

        $sentiments = $movie->reviews()->where('is_hidden', false)->whereNotNull('sentiment')
            ->selectRaw('sentiment, COUNT(*) as total')->groupBy('sentiment')->pluck('total', 'sentiment');

        return [
            'average' => $stats->average ? round($stats->average, 2) : null,
            'total' => (int) $stats->total,
            'breakdown' => $breakdown,
            'sentiments' => $sentiments,
            'reviews' => $reviews,
        ];
    }

    public function store(Request $request, $movieId, SentimentClient $sentiment)
    {
        $movie = Movie::findOrFail($movieId);
        $user = $request->user();

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'nullable|string|max:3000',
        ]);

        $review = Review::where('user_id', $user->id)->where('movie_id', $movie->id)->first();
        $isNew = ! $review;

        if ($isNew) {
            $review = new Review;
            $review->user_id = $user->id;
            $review->movie_id = $movie->id;
        }

        $review->rating = $data['rating'];
        $review->body = $data['body'] ?? null;

        $result = $sentiment->analyze($review->body);
        $review->sentiment = $result['sentiment'] ?? null;
        $review->sentiment_score = $result['sentiment_score'] ?? null;
        $review->save();

        if ($isNew) {
            Activity::log($user, $review->body ? 'reviewed' : 'rated', [
                'movie_id' => $movie->id,
                'meta' => ['rating' => $review->rating],
            ]);
        }

        return response()->json([
            'message' => $isNew ? 'Review published !' : 'Review updated !',
            'review' => $review->load('user:id,name,username,avatar_url,role'),
        ], $isNew ? 201 : 200);
    }

    public function update(Request $request, $id, SentimentClient $sentiment)
    {
        $review = Review::findOrFail($id);

        if ($review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only edit your own reviews.'], 403);
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'nullable|string|max:3000',
        ]);

        $review->rating = $data['rating'];
        $review->body = $data['body'] ?? null;
        $result = $sentiment->analyze($review->body);
        $review->sentiment = $result['sentiment'] ?? null;
        $review->sentiment_score = $result['sentiment_score'] ?? null;
        $review->save();

        return response()->json(['message' => 'Review updated !', 'review' => $review]);
    }

    public function destroy(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        if ($review->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'You can only delete your own reviews.'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted.']);
    }

    public function mine(Request $request)
    {
        return $request->user()->reviews()->with('movie:id,title,poster_url,release_date')->latest()->paginate(20);
    }
}
