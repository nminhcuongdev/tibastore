<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        $users = User::query()
            ->where('delflag', false)
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($search) use ($query) {
                    $search->where('code', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%");
                });
            })
            ->orderByRaw("CASE WHEN role = ? THEN 0 ELSE 1 END", [User::ROLE_ADMIN])
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'query' => $query,
            'roles' => User::roles(),
        ]);
    }

    public function create(): View
    {
        return view('users.form', [
            'user' => new User(['role' => User::DEFAULT_ROLE]),
            'roles' => User::roles(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        User::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'role' => $data['role'],
            'password' => $data['password'],
            'delflag' => false,
        ]);

        return redirect()
            ->route('users.index')
            ->with('status', 'Đã tạo người dùng.');
    }

    public function edit(User $user): View
    {
        if ($user->delflag) {
            abort(404);
        }

        return view('users.form', [
            'user' => $user,
            'roles' => User::roles(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->delflag) {
            abort(404);
        }

        $data = $this->validatedData($request, $user);

        // Không cho phép hạ quyền admin cuối cùng (tránh khoá hết quản trị).
        if ($user->isAdmin()
            && $data['role'] !== User::ROLE_ADMIN
            && $this->activeAdminCount(exceptId: $user->id) === 0) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Không thể hạ quyền admin cuối cùng của hệ thống.']);
        }

        $user->fill([
            'code' => $data['code'],
            'name' => $data['name'],
            'role' => $data['role'],
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('status', 'Đã cập nhật người dùng.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->delflag) {
            abort(404);
        }

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Không thể xóa tài khoản đang đăng nhập.']);
        }

        if ($user->isAdmin() && $this->activeAdminCount(exceptId: $user->id) === 0) {
            return back()->withErrors(['user' => 'Không thể xóa admin cuối cùng của hệ thống.']);
        }

        $user->update(['delflag' => true]);

        return redirect()
            ->route('users.index')
            ->with('status', 'Đã xóa người dùng.');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $passwordRules = $user === null
            ? ['required', 'string', 'min:6', 'confirmed']
            : ['nullable', 'string', 'min:6', 'confirmed'];

        return $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('users', 'code')->ignore($user?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(array_keys(User::roles()))],
            'password' => $passwordRules,
        ], [
            'code.required' => 'Vui lòng nhập mã đăng nhập.',
            'code.unique' => 'Mã đăng nhập đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên người dùng.',
            'role.required' => 'Vui lòng chọn quyền.',
            'role.in' => 'Quyền không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);
    }

    private function activeAdminCount(?int $exceptId = null): int
    {
        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('delflag', false)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->count();
    }
}
