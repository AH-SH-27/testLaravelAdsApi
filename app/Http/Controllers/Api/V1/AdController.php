<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdRequest;
use App\Http\Resources\AdCollection;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Services\AdService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdController extends Controller
{

    public function __construct(private AdService $adService) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdRequest $request): JsonResponse
    {
        try {
            $ad = $this->adService->createAd(
                $request->validated(),
                $request->user()->id
            );

            return (new AdResource($ad))
                ->response()
                ->setStatusCode(201);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error creating ad', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create ad due to database error',
                'error' => config('app.debug') ? $e->getMessage() : 'Please try again later',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error creating ad', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to create ad',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred',
            ], 500);
        }
    }


    public function myAds(Request $request): JsonResponse
    {
        $ads = Ad::where('user_id', $request->user()->id)
            ->with(['category', 'fieldValues.categoryField'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return (new AdCollection($ads))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ad $ad): JsonResponse
    {
        try {
            $ad->load(['category', 'fieldValues.categoryField']);

            return (new AdResource($ad))
                ->response()
                ->setStatusCode(200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Ad not found',
            ], 404);
        }
    }
}
