<?php
namespace App\Policies;

use App\Models\Business;
use App\Models\BusinessOwner;
use Illuminate\Auth\Access\HandlesAuthorization;

class BusinessPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the business owner can update the business.
     *
     * @param  \App\Models\BusinessOwner  $businessOwner
     * @param  \App\Models\Business  $business
     * @return mixed
     */
    public function update(BusinessOwner $businessOwner, Business $business)
    {
        return $business->business_owner_id === $businessOwner->id;
    }
}
