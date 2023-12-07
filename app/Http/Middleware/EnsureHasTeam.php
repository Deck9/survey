<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasTeam
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->expectsJson()) {
            return $this->handleApi($request, $next);
        } else {
            return $this->handleWeb($request, $next);
        }
    }

    protected function handleApi(Request $request, Closure $next): Response
    {
        // if user is not logged in, allow them to proceed
        if (! $request->user()) {
            return $next($request);
        }

        // check if user has a current team
        // if they do, allow them to proceed
        if ($request->user()->currentTeam) {
            return $next($request);
        }

        // otherwise, get all teams for the user
        $teams = $request->user()->allTeams();

        // if user has no team at this point, return a 403
        if ($teams->count() === 0) {
            return abort(403, 'You are not a member of any team.');
        }

        // otherwise, switch to the first team
        $request->user()->switchTeam($teams->first());

        return $next($request);
    }

    protected function handleWeb(Request $request, Closure $next): Response
    {
        // if user is not logged in, allow them to proceed
        if (! $request->user()) {
            return $next($request);
        }

        // check if user has a current team
        // if they do, allow them to proceed
        if ($request->user()->currentTeam) {
            return $next($request);
        }

        if ($request->route()->getName() === 'teams.missing') {
            return $next($request);
        }

        // if user is accepting an invitation, allow them to proceed
        if ($request->route()->getName() === 'team-invitations.accept') {
            return $next($request);
        }

        // otherwise, get all teams for the user
        $teams = $request->user()->allTeams();

        // if user has no team at this point, redirect them to the missing team page
        if ($teams->count() === 0) {
            return redirect()->route('teams.missing');
        }

        // otherwise, switch to the first team
        $request->user()->switchTeam($teams->first());

        return $next($request);
    }
}
