<?php

namespace App\Models;

use App\Models\Scopes\InspectionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Inspection extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'contact_inspector_info' => 'json',
        'servicer_loan_info' => 'json',
        'management_onsite_info' => 'json',
        'comments' => 'json',
        'profile_occupancy_info' => 'json',
        'capital_expenditures' => 'json',
        'operation_maintenance_plans' => 'json',
        'neighborhood_site_data' => 'json',
        'physical_condition' => 'json',
        'images' => 'json',
        'rent_roll' => 'json',
        'form_steps' => 'json',
        'mgmt_interview' => 'json',
        'multifamily' => 'json',
        'fannie_mae_assmt' => 'json',
        'fre_assmt' => 'json',
        'repairs_verification' => 'json',
        'senior_supplement' => 'json',
        'hospitals' => 'json',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        if (auth()->check()) {
            if (!auth()->user()->is_admin) {
                static::addGlobalScope(new InspectionScope);
            }
        }

        static::deleted(function ($model) {

            // Delete associated photos from storage
            if(!is_null($model->images)) {
                $model->deletePhotos($model->images);
            }
            if(!is_null($model->repairs_verification)) {
                $model->deletePhotos($model->repairs_verification['verification_list']);
            }
        });

        static::updated(function ($model) {

            // Delete old photos only if they are replaced with new ones
            $originalImages = $model->getOriginal('images');
            $originalRepairsVerification = $model->getOriginal('repairs_verification');

            $currentImages = $model->images;
            $currentRepairsVerification = $model->repairs_verification;

            if(!is_null($model->repairs_verification))
            {
                $model->deleteOldPhotos($originalRepairsVerification['verification_list'], $currentRepairsVerification['verification_list']);

            }
            if(!is_null($model->images))
            {
                $model->deleteOldPhotos($originalImages, $currentImages);
            }

        });

    }

    protected function deletePhotos($photos): void
    {
        foreach ($photos as $photo) {
            $photoField = array_key_exists('photo', $photo) ? 'photo' : 'photo_url';
            if (is_array($photo[$photoField])) {
                foreach ($photo[$photoField] as $photo_ind) {
                    $photoPath = "public/{$photo_ind}";
                    if (Storage::exists($photoPath)) {
                        Storage::delete($photoPath);
                    }
                    if (Storage::disk('s3')->exists($photo_ind)) {
                        Storage::delete($photo_ind);
                    }
                }
            } else {
                $photoPath = "public/{$photo[$photoField]}";
                if (Storage::exists($photoPath)) {
                    Storage::delete($photoPath);
                }
                if (Storage::disk('s3')->exists($photo[$photoField])) {
                    Storage::delete($photo[$photoField]);
                }

            }
        }


    }

    protected function deleteOldPhotos($originalPhotos, $currentPhotos): void
    {
        foreach ($originalPhotos as $originalPhoto) {
            $originalPhotoField = array_key_exists('photo', $originalPhoto) ? 'photo' : 'photo_url';
            $originalPhotoUrl = $originalPhoto[$originalPhotoField];

            if (is_array($originalPhotoUrl)) {
                foreach ($originalPhotoUrl as $singlePhoto) {
                    if (!collect($currentPhotos)->pluck($originalPhotoField)->flatten()->contains($singlePhoto)) {
                        $photoPath = "public/{$singlePhoto}";

                        if (Storage::exists($photoPath)) {
                            Storage::delete($photoPath);
                        }
                        if (Storage::disk('s3')->exists($singlePhoto)) {
                            Storage::delete($singlePhoto);
                        }

                    }
                }
            } else {
                if (!collect($currentPhotos)->pluck($originalPhotoField)->contains($originalPhotoUrl)) {
                    $photoPath = "public/{$originalPhotoUrl}";

                    if (Storage::exists($photoPath)) {
                        Storage::delete($photoPath);
                    }
                    if (Storage::disk('s3')->exists($originalPhotoUrl)) {
                        Storage::delete($originalPhotoUrl);
                    }
                }
            }
        }

    }
}
