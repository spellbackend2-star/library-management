<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    public function __construct(
        protected MemberService $memberService
    ) {}

    public function index()
    {
        return MemberResource::collection(
            $this->memberService->getAll()
        );
    }

    public function store(StoreMemberRequest $request): MemberResource
    {
        $member = $this->memberService->create(
            $request->validated()
        );

        return new MemberResource($member->load('membershipType'));
    }

    public function show(int $member): MemberResource
    {
        $memberData = $this->memberService->getById($member);

        abort_if(!$memberData, 404, 'Member not found.');

        return new MemberResource($memberData);
    }

    public function update(
        UpdateMemberRequest $request,
        int $member
    ): MemberResource {
        $memberData = $this->memberService->update(
            $member,
            $request->validated()
        );

        return new MemberResource($memberData);
    }

    public function destroy(int $member): JsonResponse
    {
        $this->memberService->delete($member);

        return response()->json([
            'message' => 'Member deleted successfully.',
        ]);
    }
}