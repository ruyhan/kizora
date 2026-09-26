<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PointTransaction;
use App\Models\User;
use App\Services\RewardPointService;
use App\Support\PointSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Admin → Privilege Members → Points: earning / redeeming rules, balances and manual adjustments. */
class RewardPointController extends Controller
{
    public function __construct(
        private readonly PointSettings $settings,
        private readonly RewardPointService $points,
    ) {}

    public function index(Request $request): Response
    {
        $customers = User::query()
            ->where('reward_points', '>', 0)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%")))
            ->orderByDesc('reward_points')
            ->paginate(15, ['id', 'name', 'email', 'phone', 'reward_points'])
            ->withQueryString();

        return Inertia::render('Admin/PrivilegeMembers/Points', [
            'settings'  => $this->settings->get(),
            'customers' => $customers,
            'filters'   => ['search' => $request->get('search', '')],
            'stats'     => [
                'outstanding' => (int) User::sum('reward_points'),
                'holders'     => User::where('reward_points', '>', 0)->count(),
                'earned'      => (int) PointTransaction::where('type', 'earn')->sum('points'),
                'redeemed'    => (int) -PointTransaction::where('type', 'redeem')->sum('points'),
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'enabled'            => 'boolean',
            'earn_amount'        => 'required|numeric|min:1|max:1000000',
            'earn_points'        => 'required|integer|min:1|max:100000',
            'point_value'        => 'required|numeric|min:0.01|max:100000',
            'min_redeem'         => 'required|integer|min:1|max:1000000',
            'max_redeem_percent' => 'required|numeric|min:1|max:100',
        ]);

        $this->settings->save($data);
        AuditLog::record('updated', 'Reward Points', 'Updated reward point settings');

        return back()->with('success', 'Point settings saved.');
    }

    /** Manually add or remove points for one customer. */
    public function adjust(Request $request, User $user)
    {
        $data = $request->validate([
            'points' => 'required|integer|not_in:0|min:-1000000|max:1000000',
            'note'   => 'nullable|string|max:200',
        ]);

        $this->points->adjust($user, (int) $data['points'], $data['note'] ?? null, auth()->id());
        AuditLog::record('adjusted', 'Reward Points', sprintf('%+d points for %s', $data['points'], $user->name));

        return back()->with('success', "Points updated — {$user->name} now has {$user->fresh()->reward_points} points.");
    }

    /** Customer lookup for the "adjust points" form (any customer, including 0 balance). */
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(User::query()
            ->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"))
            ->limit(8)->get(['id', 'name', 'email', 'reward_points']));
    }
}
