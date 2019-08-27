<?php

namespace Karla\Http\Controllers\Account;

use App\Http\Controllers\Controller as BaseController;
use App\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Controller extends BaseController
{
    public function getViewsFrom(): array
    {
        return [__DIR__];
    }

    public function index(): array
    {
        $user_id = user('id');
        $user    = User::find($user_id);

        if ($this->isMethod('post')) {
            $this->rules([
                'name'   => 'required',
                'email'  => 'required|email',
                'mobile' => 'required',
            ]);

            $password = user('password');
            if (!Hash::check($this->input('password'), $password)) {
                return [
                    'status'  => 'ERROR',
                    'message' => __('Your current password didn\'t match.'),
                ];
            }

            $email = $this->input('email');
            // Check email is changed
            if ($user->email != $email) {
                $exists = $this->table('auth_users')
                    ->where('email', $email)
                    ->where('id', '!=', $user_id)
                    ->exists();

                if ($exists) {
                    return [
                        'status'  => 'ERROR',
                        'message' => __('Email address already registered.'),
                    ];
                }
            }

            $user->name   = $this->input('name');
            $user->email  = $this->input('email');
            $user->mobile = $this->input('mobile');

            $result = $user->save();

            return $this->updated($result, 'account');
        }

        return [
            'user' => $user,
        ];
    }

    public function password(): array
    {
        if ($this->isMethod('post')) {
            $this->rules([
                'oldpassword'      => 'required',
                'password'         => 'required|min:6',
                'password_confirm' => 'required|same:password',
            ]);

            $user_id = user('id');
            $user    = User::find($user_id);

            $password = $user->password;
            $inputpwd = $this->input('password');

            if (!Hash::check($this->input('oldpassword'), $password)) {
                return [
                    'status'  => 'ERROR',
                    'message' => __('Your current password didn\'t match.'),
                ];
            }

            if (Hash::check($inputpwd, $password)) {
                return [
                    'status'  => 'ERROR',
                    'message' => __('New password shouldn\'t be same as old one.'),
                ];
            }

            $limit = 3;

            $rows = $this->db->table('auth_password_history')
                ->where('user_id', $user_id)
                ->orderBy('created_at', 'DESC')
                ->take($limit)
                ->pluck('password');

            foreach ($rows as $pwd) {
                if (Hash::check($inputpwd, $pwd)) {
                    return [
                        'status'  => 'ERROR',
                        'message' => __('Password shouldn\'t be same as last :limit passwords.', ['limit' => $limit]),
                    ];
                }
            }

            $user->password = Hash::make($inputpwd);

            $user->last_password_at = carbon();

            $result = $user->save();

            if ($result) {
                $save               = [];
                $save['user_id']    = $user_id;
                $save['created_at'] = carbon();
                $save['password']   = $user->password;

                $this->table('auth_password_history', false)
                    ->insert($save);

                $values = [
                    'password' => $inputpwd,
                ];

                (new Mailable())
                    ->subject('Your password has been changed!!!')
                    ->with([
                        'row'  => $values,
                        'user' => $user,
                    ])
                    ->markdown('karla::emails.auth.password_changed')
                    ->deliver($user);
            }

            return $this->updated($result, 'password');
        }

        return [];
    }

    public function sniff($key)
    {
        $decrypted = null;

        try {
            $decrypted = decrypt($key);
        } catch (DecryptException $e) {
            abort(401, 'Unable to decrypt');
        }

        $values = \explode('|', $decrypted);

        $user_id = $values[0];
        $login   = carbon($values[1]);

        // get UserById
        $user = User::where('id', $user_id)
            ->where('status', 1)
            ->first();

        if (\is_null($user)) {
            abort(401, 'No user');
        }

        $last = carbon($user->last_login_at);
        // Compare time
        if ($user->last_login_at && $login->ne($last)) {
            abort(401, 'Time mismatch');
        }

        Auth::guard()->logout();
        $result = Auth::guard()->loginUsingId($user->id);

        if (!$result) {
            abort(401, 'Unable to login');
        }

        $user->last_login_at = carbon();
        $user->save();

        return redirect()->route('home');
    }

    public function search(): array
    {
        $term = $this->query('term');
        if (empty($term)) {
            return [];
        }

        $rows = $this->table('auth_users')
            ->where('name', 'like', '%' . $term . '%')
            ->where('id', '<>', user('id'))
            ->get(['name as text', 'id']);

        return [
            'rows' => $rows,
        ];
    }

}