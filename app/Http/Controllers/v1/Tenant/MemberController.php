<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\MemberResource;
use App\Services\InvoiceService;
use App\Services\MemberService;
use App\Traits\ResponseMessage;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    use ResponseMessage;

    public function __construct(
        protected MemberService $memberService,
        protected InvoiceService $invoiceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->memberService->getAll(
            $request->all()
        );

        return response()->json([
            'success' => true,
            'message' => 'Members retrieved successfully.',
            'data' => MemberResource::collection(
                $result['data']
            ),
            'meta' => $result['meta'],
        ]);
    }

    public function all(Request $request): JsonResponse
    {
        $members = $this->memberService->getAllWithoutPagination(
            $request->all()
        );

        return $this->successResponse(
            MemberResource::collection($members),
            'Members retrieved successfully.'
        );
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $data = $request->validated();

        $withInvoice = $data['with_invoice'] ?? true;

        [$member, $invoice] = DB::transaction(
            function () use ($data, $withInvoice) {

                unset($data['with_invoice']);

                if ($withInvoice) {
                    // A new member stays suspended until the invoice is fully paid.
                    $data['status'] = 'suspended';
                }

                $member = $this->memberService->create(
                    $data
                );

                if (! $withInvoice) {
                    $member->update([
                        'paid_through' => $member->membership_expiry,
                    ]);

                    return [$member, null];
                }

                $package = $member->package;

                if (! $package) {
                    abort(
                        422,
                        'Member package not found. Cannot create invoice.'
                    );
                }

                $invoice = $this->invoiceService->create([
                    'member_id' => $member->id,
                    'total_amount' => $package->price,
                    'coupon_id' => $data['coupon_id'] ?? null,
                ]);

                return [$member, $invoice];
            }
        );

        $data = [
            'member' => new MemberResource(
                $member->load('package')
            ),
        ];

        if ($invoice) {
            $data['invoice'] = new InvoiceResource(
                $invoice
            );
        }

        return $this->successResponse(
            $data,
            $withInvoice
                ? 'Member and invoice created successfully.'
                : 'Member created successfully.',
            201
        );
    }

    public function show(int $member): JsonResponse
    {
        $memberData = $this->memberService->getById(
            $member
        );

        abort_if(
            ! $memberData,
            404,
            'Member not found.'
        );

        return $this->successResponse(
            new MemberResource($memberData),
            'Member retrieved successfully.'
        );
    }

    public function update(
        UpdateMemberRequest $request,
        int $member
    ): JsonResponse {
        $memberData = $this->memberService->update(
            $member,
            $request->validated()
        );

        return $this->successResponse(
            new MemberResource($memberData),
            'Member updated successfully.'
        );
    }

    public function suspend(int $member): JsonResponse
    {
        $memberData = $this->memberService->getById($member);

        if (! $memberData) {
            return $this->errorResponse('Member not found.', 404);
        }

        if ($memberData->status !== 'active') {
            return $this->errorResponse('Only active members can be suspended.', 422);
        }

        if (
            ! $memberData->membership_expiry
            || $memberData->membership_expiry->lt(Carbon::today())
        ) {
            return $this->errorResponse(
                'A valid membership expiry date (today or later) is required to suspend this member.',
                422
            );
        }

        $memberData = $this->memberService->suspend($member);

        return $this->successResponse(
            new MemberResource($memberData),
            'Member suspended successfully.'
        );
    }

    public function activate(int $member): JsonResponse
    {
        $memberData = $this->memberService->getById($member);

        if (! $memberData) {
            return $this->errorResponse('Member not found.', 404);
        }

        if ($memberData->status !== 'suspended') {
            return $this->errorResponse('Only suspended members can be activated.', 422);
        }

        if (
            ! $memberData->membership_expiry
            || $memberData->membership_expiry->lt(Carbon::today())
        ) {
            return $this->errorResponse(
                'This membership has expired. Renew the membership before activating the member.',
                422
            );
        }

        if (
            ! $memberData->paid_through
            || $memberData->paid_through->lt(Carbon::today())
        ) {
            return $this->errorResponse(
                'This member has no paid membership days remaining. Record a payment before activating them.',
                422
            );
        }

        $memberData = $this->memberService->activate($member);

        return $this->successResponse(
            new MemberResource($memberData),
            'Member activated successfully.'
        );
    }

    public function destroy(int $member): JsonResponse
    {
        try {
            $this->memberService->delete($member);
        } catch (QueryException $exception) {
            if (
                $exception->getCode() === '23000'
                && (int) ($exception->errorInfo[1] ?? 0) === 1451
            ) {
                return $this->errorResponse(
                    'This member cannot be deleted because invoices or other records are linked to them. Keep the member record and change their status to suspended or cancelled instead.',
                    409
                );
            }

            throw $exception;
        }

        return $this->successResponse(
            null,
            'Member deleted successfully.'
        );
    }
}
