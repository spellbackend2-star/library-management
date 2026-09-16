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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $data = $request->validated();

        $withInvoice = $data['with_invoice'] ?? false;

        [$member, $invoice] = DB::transaction(
            function () use ($data, $withInvoice) {

                unset($data['with_invoice']);

                $member = $this->memberService->create(
                    $data
                );

                if (! $withInvoice) {
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

    public function destroy(int $member): JsonResponse
    {
        $this->memberService->delete($member);

        return $this->successResponse(
            null,
            'Member deleted successfully.'
        );
    }
}
