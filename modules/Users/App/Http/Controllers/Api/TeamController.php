<?php
 

namespace Modules\Users\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Core\App\Http\Controllers\ApiController;
use Modules\Users\App\Http\Requests\TeamRequest;
use Modules\Users\App\Http\Resources\TeamResource;
use Modules\Users\App\Models\Team;

class TeamController extends ApiController
{
    /**
     * Retrieve all teams.
     */
    public function index(): JsonResponse
    {
        $teams = Team::with(['users', 'manager'])
            ->userTeams()
            ->orderBy('name')
            ->get();

        return $this->response(
            TeamResource::collection($teams)
        );
    }

    /**
     * Create new team.
     */
    public function store(TeamRequest $request): JsonResponse
    {
        $team = Team::create($request->input());

        if ($request->has('members')) {
            $team->users()->attach($request->input('members', []));
        }

        $team->load(['users', 'manager']);

        return $this->response(
            new TeamResource($team),
            JsonResponse::HTTP_CREATED
        );
    }

    /**
     * Retrieve a team.
     */
    public function show(Team $team): JsonResponse
    {
        $team = $team->loadMissing(['users', 'manager']);

        /** @var \Modules\Users\App\Models\User */
        $user = Auth::user();

        abort_if(! $user->isSuperAdmin() && ! $user->belongsToTeam($team), 403);

        return $this->response(
            new TeamResource($team)
        );
    }

    /**
     * Update a team.
     */
    public function update(Team $team, TeamRequest $request): JsonResponse
    {
        $team->fill($request->input())->save();

        if ($request->has('members')) {
            $team->users()->sync($request->input('members', []));
        }

        $team->load(['users', 'manager']);

        return $this->response(
            new TeamResource($team),
        );
    }

    /**
     * Delete a team.
     */
    public function destroy(Team $team): JsonResponse
    {
        $team->delete();

        return $this->response('', JsonResponse::HTTP_NO_CONTENT);
    }
}
