<?php
namespace App\Http\Resources\User;

use App\Http\Resources\CoreApp\DepartementResource;
use App\Http\Resources\CoreApp\JobLevelResource;
use App\Http\Resources\CoreApp\JobPositionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'nip' => $this->nip,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'device_id' => $this->device_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'details' => new UserDetailResource($this->details),
            'address' => new UserAddressResource($this->address),
            'salaries' => new UserSalaryResource($this->salaries),
            'families' => UserFamilyResource::collection($this->families),
            'formal_educations' => UserFormalEducationResource::collection($this->formalEducations),
            'informal_educations' => UserInformalEducationResource::collection($this->informalEducations),
            'work_experiences' => UserWorkExperienceResource::collection($this->workExperiences),
            'employee' => new UserEmployeeResource($this->employee),
            // 'departement_info' => new DepartementResource($this->departement_info),
            // 'position_info' => new JobPositionResource($this->position_info),
            // 'level_info' => new JobLevelResource($this->level_info),
        ];
    }
}
