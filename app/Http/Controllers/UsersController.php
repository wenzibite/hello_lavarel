<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Mail;
class UsersController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store', 'index', 'confirmEmail']
        ]);

        $this->middleware('guest', [
            'only' => ['create']
        ]);
    }

    public function index()
    {
        // $users = User::all();
        $users = User::paginate(10);
        return view('users.index', compact('users'));
    }
    //
    public function create()
    {
        return view('users.create');
    }

    public function show(User $user)
    {
        $statuses = $user->feed()->paginate(10);
        return view('users.show', compact('user','statuses'));
    }

    //
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:users|max:50',
            'email'=> 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ]);
        //保存用户信息
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        // //自动登录
        // Auth::login($user);
        //注册成功后页面顶部提示信息
        // session()->flash('success', '欢迎，您将在这里开启一段新的旅程~');
        // //重定向到用户页面
        // return redirect()->route('users.show', [$user]);

        //发送激活邮件
        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮箱已发送到您的注册邮箱上，请注意查收。');
        return redirect('/');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    public function update(User $user, Request $request)
    {
        $this->authorize('update', $user);
        $return = $this->validate($request, [
            'name' => 'required|max:50',
            'password' => 'nullable|confirmed|min:6'
        ]);
        $data = [];
        $data['name'] = $request->name;
        if($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);
        session()->flash('success', '个人资料更新成功！');
        return redirect()->route('users.show', $user);
    }

    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }

    //发送邮件
    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        // $from = 'summer@example.com';
        // $name = 'Summer';
        $to = $user->email;
        $subject = "感谢注册 weibo 应用！请确认您的邮箱。";

        // Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject) {
        //     $message->from($from,$name)->to($to)->subject($subject);
        // });
        Mail::send($view, $data, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }

    //激活
    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', '恭喜你，激活成功！');
        return redirect()->route('users.show', $user->id);
    }

    //关注的人
    public function followings(User $user)
    {
        $users = $user->followings()->paginate(10);
        $title = $user->name . '关注的人';
        return view('users.show_follow', compact('users', 'title'));
    }

    //粉丝列表
    public function followers(User $user)
    {
        $users = $user->followers()->paginate(10);
        $title = $user->name . '的粉丝';
        return view('users.show_follow', compact('users', 'title'));
    }
}
