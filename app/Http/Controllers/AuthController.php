<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Hiển thị trang tài khoản hoặc đăng nhập/đăng ký
     */
    public function index()
    {
        if (Auth::check()) {
            $user = Auth::user();
            // Lấy các đơn hàng thực tế của user (tìm theo số điện thoại hoặc email)
            // Vì Order được lưu theo phone/email nên ta có thể lọc theo phone/email của user.
            $orders = Order::with('items.product')
                ->where('customer_phone', $user->phone)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Lấy danh sách voucher khả dụng từ DB
            $vouchers = Voucher::where('expires_at', '>', Carbon::now())
                ->where('quantity', '>', 0)
                ->orderBy('expires_at', 'asc')
                ->get();

            return view('auth', compact('user', 'orders', 'vouchers'));
        }

        return view('auth');
    }

    /**
     * Xử lý Đăng ký
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'phone' => 'required|string|max:15',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'email.unique' => 'Email này đã được đăng ký sử dụng.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có tối thiểu 6 ký tự.'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('page.auth')->with('success', 'Đăng ký tài khoản thành công!');
    }

    /**
     * Xử lý Đăng nhập
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            $request->session()->regenerate();
            if (Auth::user()->is_admin) {
                return redirect()->route('admin.dashboard')->with('success', 'Đăng nhập trang quản trị thành công!');
            }
            return redirect()->route('page.auth')->with('success', 'Đăng nhập thành công!');
        }

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ])->onlyInput('email');
    }

    /**
     * Xử lý Đăng xuất
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Đã đăng xuất khỏi tài khoản.');
    }

    /**
     * Trang yêu cầu quên mật khẩu
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Xử lý gửi yêu cầu quên mật khẩu
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Không tìm thấy tài khoản với email này.']);
        }

        $token = Str::random(60);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => Carbon::now()]
        );

        $resetLink = route('password.reset', ['token' => $token, 'email' => $request->email]);

        // Thực hiện gửi email thực tế qua SMTP
        try {
            Mail::to($user->email)->send(new ResetPasswordMail($resetLink, $user->name));
            $emailSent = true;
        } catch (\Exception $e) {
            $emailSent = false;
            $mailError = $e->getMessage();
        }

        if ($emailSent) {
            return back()->with([
                'success' => 'Chúng tôi đã gửi email hướng dẫn khôi phục mật khẩu tới ' . $user->email . '.'
            ]);
        } else {
            return back()->withErrors([
                'email' => 'Đã có lỗi xảy ra khi gửi email: ' . $mailError . '. Hãy đảm bảo cấu hình SMTP của bạn chính xác.'
            ]);
        }
    }

    /**
     * Trang hiển thị form đặt lại mật khẩu
     */
    public function showResetPassword(Request $request)
    {
        return view('auth.reset-password', [
            'token' => $request->token,
            'email' => $request->email
        ]);
    }

    /**
     * Xử lý đặt lại mật khẩu mới
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có tối thiểu 6 ký tự.'
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Token khôi phục mật khẩu không hợp lệ hoặc đã hết hạn.']);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->update(['password' => Hash::make($request->password)]);
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return redirect()->route('page.auth')->with('success', 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập.');
        }

        return back()->withErrors(['email' => 'Lỗi hệ thống, vui lòng thử lại sau.']);
    }

    /**
     * Giả lập trang đăng nhập Google
     */
    public function redirectToGoogle()
    {
        return view('auth.social-consent', ['provider' => 'Google']);
    }

    /**
     * Giả lập callback đăng nhập Google
     */
    public function handleGoogleCallback(Request $request)
    {
        $email = $request->input('email', 'google.user@gmail.com');
        $name = $request->input('name', 'Khách hàng Google');

        // Tìm hoặc tạo mới người dùng
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(24)),
                'email_verified_at' => Carbon::now(),
            ]);
        }

        Auth::login($user);

        return redirect()->route('page.auth')->with('success', 'Đăng nhập bằng Google thành công!');
    }

    /**
     * Giả lập trang đăng nhập Facebook
     */
    public function redirectToFacebook()
    {
        return view('auth.social-consent', ['provider' => 'Facebook']);
    }

    /**
     * Giả lập callback đăng nhập Facebook
     */
    public function handleFacebookCallback(Request $request)
    {
        $email = $request->input('email', 'facebook.user@gmail.com');
        $name = $request->input('name', 'Khách hàng Facebook');

        // Tìm hoặc tạo mới người dùng
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(24)),
                'email_verified_at' => Carbon::now(),
            ]);
        }

        Auth::login($user);

        return redirect()->route('page.auth')->with('success', 'Đăng nhập bằng Facebook thành công!');
    }

    /**
     * Hiển thị trang chỉnh sửa thông tin cá nhân
     */
    public function profile()
    {
        $user = Auth::user();
        return view('auth.profile', compact('user'));
    }

    /**
     * Xử lý cập nhật thông tin cá nhân & upload avatar lên MinIO
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:15',
            'email' => 'required|string|email|max:100|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|max:4096',
        ], [
            'email.unique' => 'Email này đã được sử dụng bởi tài khoản khác.',
            'avatar.image' => 'Ảnh đại diện phải là tệp hình ảnh.',
            'avatar.max' => 'Ảnh đại diện tối đa là 4MB.'
        ]);

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            try {
                $s3 = \Illuminate\Support\Facades\Storage::disk('s3');
                $client = $s3->getClient();
                $bucket = config('filesystems.disks.s3.bucket');
                $this->ensureMinioBucket($client, $bucket);
                
                // Xóa ảnh cũ trên s3 nếu có
                if ($user->avatar && str_starts_with($user->avatar, 'avatars/')) {
                    $s3->delete($user->avatar);
                }
                
                $s3->putFileAs('avatars', $file, $filename, 'public');
                $user->avatar = 'avatars/' . $filename;
            } catch (\Exception $e) {
                \Log::error('MinIO avatar upload failed: ' . $e->getMessage());
                // Fallback to local public disk
                $filename = $file->store('avatars', 'public');
                $user->avatar = $filename;
            }
        }

        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->save();

        return redirect()->route('page.profile')->with('success', 'Cập nhật thông tin cá nhân thành công!');
    }

    /**
     * Đảm bảo MinIO Bucket tồn tại
     */
    private function ensureMinioBucket($client, $bucket)
    {
        try {
            if (!$client->doesBucketExist($bucket)) {
                $client->createBucket([
                    'Bucket' => $bucket,
                ]);
            }
            
            $policy = json_encode([
                'Version' => '2012-10-17',
                'Statement' => [
                    [
                        'Sid' => 'PublicRead',
                        'Effect' => 'Allow',
                        'Principal' => '*',
                        'Action' => ['s3:GetObject'],
                        'Resource' => ["arn:aws:s3:::{$bucket}/*"]
                    ]
                ]
            ]);
            
            $client->putBucketPolicy([
                'Bucket' => $bucket,
                'Policy' => $policy,
            ]);
        } catch (\Exception $e) {
            \Log::error('MinIO bucket setup failed: ' . $e->getMessage());
        }
    }
}
