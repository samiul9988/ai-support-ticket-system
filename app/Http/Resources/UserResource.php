<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'role' => $this->whenLoaded('role', function () {
                return [
                    'id' => $this->role->id,
                    'name' => $this->role->name,
                    'slug' => $this->role->slug,
                    'permissions' => $this->when(
                        $this->role->relationLoaded('permissions'),
                        fn () => $this->role->permissions->pluck('slug')
                    ),
                ];
            }),
            'created_at' => $this->created_at,
        ];
    }
}
