<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserSearchController extends Controller
{
    /**
     * Search users for Select2 AJAX (q param)
     */
    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        $query = User::query()->select(['id', 'name', 'email'])
            ->where('is_active', 1)
            ->when($q, function ($qbuilder) use ($q) {
                $qbuilder->where(function ($b) use ($q) {
                    $b->where('name', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%");
                });
            });

        $total = $query->count();
        $results = $query->orderBy('name')->skip(($page - 1) * $perPage)->take($perPage)->get();

        $items = $results->map(function ($u) {
            return ['id' => $u->id, 'text' => $u->name . ' (' . $u->email . ')'];
        });

        return response()->json([
            'results' => $items,
            'pagination' => ['more' => ($page * $perPage) < $total]
        ]);
    }
}
