<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStudySettingsRequest;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudySettingsController extends Controller
{
    /**
     * Show the study settings form.
     */
    public function edit(Request $request): View
    {
        return view('settings.edit', [
            'user' => $request->user(),
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    /**
     * Save the study settings.
     */
    public function update(UpdateStudySettingsRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('settings.edit')->with('status', 'settings-updated');
    }
}
