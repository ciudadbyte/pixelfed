<?php

namespace App\Http\Controllers\Settings;

use App\AccountLog;
use App\EmailVerification;
use App\Media;
use App\Profile;
use App\User;
use App\UserFilter;
use App\Util\Lexer\PrettyNumber;
use Auth;
use DB;
use Illuminate\Http\Request;

trait PrivacySettings
{

    public function privacy()
    {
        $settings = Auth::user()->settings;
        $is_private = Auth::user()->profile->is_private;
        $settings['is_private'] = (bool) $is_private;

        return view('settings.privacy', compact('settings'));
    }

    public function privacyStore(Request $request)
    {
        $settings = Auth::user()->settings;
        $profile = Auth::user()->profile;
        $fields = [
          'is_private',
          'crawlable',
          'show_profile_follower_count',
          'show_profile_following_count',
      ];
        foreach ($fields as $field) {
            $form = $request->input($field);
            if ($field == 'is_private') {
                if ($form == 'on') {
                    $profile->{$field} = true;
                    $settings->show_guests = false;
                    $settings->show_discover = false;
                    $profile->save();
                } else {
                    $profile->{$field} = false;
                    $profile->save();
                }
            } elseif ($field == 'crawlable') {
                if ($form == 'on') {
                    $settings->{$field} = false;
                } else {
                    $settings->{$field} = true;
                }
            } else {
                if ($form == 'on') {
                    $settings->{$field} = true;
                } else {
                    $settings->{$field} = false;
                }
            }
            $settings->save();
        }

        return redirect(route('settings.privacy'))->with('status', 'Settings successfully updated!');
    }

    public function mutedUsers()
    {   
        $pid = Auth::user()->profile->id;
        $ids = (new UserFilter())->mutedUserIds($pid);
        $users = Profile::whereIn('id', $ids)->simplePaginate(15);
        return view('settings.privacy.muted', compact('users'));
    }

    public function mutedUsersUpdate(Request $request)
    {   
        $this->validate($request, [
            'profile_id' => 'required|integer|min:1'
        ]);
        $fid = $request->input('profile_id');
        $pid = Auth::user()->profile->id;
        DB::transaction(function () use ($fid, $pid) {
            $filter = UserFilter::whereUserId($pid)
                ->whereFilterableId($fid)
                ->whereFilterableType('App\Profile')
                ->whereFilterType('mute')
                ->firstOrFail();
            $filter->delete();
        });
        return redirect()->back();
    }

    public function blockedUsers()
    {
        $pid = Auth::user()->profile->id;
        $ids = (new UserFilter())->blockedUserIds($pid);
        $users = Profile::whereIn('id', $ids)->simplePaginate(15);
        return view('settings.privacy.blocked', compact('users'));
    }


    public function blockedUsersUpdate(Request $request)
    {   
        $this->validate($request, [
            'profile_id' => 'required|integer|min:1'
        ]);
        $fid = $request->input('profile_id');
        $pid = Auth::user()->profile->id;
        DB::transaction(function () use ($fid, $pid) {
            $filter = UserFilter::whereUserId($pid)
                ->whereFilterableId($fid)
                ->whereFilterableType('App\Profile')
                ->whereFilterType('block')
                ->firstOrFail();
            $filter->delete();
        });
        return redirect()->back();
    }

    public function blockedInstances()
    {
        $settings = Auth::user()->settings;
        return view('settings.privacy.blocked-instances');
    }
}