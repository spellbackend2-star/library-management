<?php

namespace App\Http\Controllers\v1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Services\MemberService;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $data = $request->validated();
        $withInvoice = $data['with_invoice'] ?? false;

        $member = DB::transaction(function () use ($data, $withInvoice) {
            $memberData = $data;
            unset($memberData['with_invoice']);

            $member = app(MemberService::class)->create($memberData);

            $invoice = null;

            if ($withInvoice) {
                $package = $member->package;

                if (!$package) {
                    throw new \Exception('Member package not found. Cannot create invoice.');
                }

                $invoice = app(InvoiceService::class)->create([
                    'member_id' => $member->id,
                    'total_amount' => $package->price,
                ]);
            }

            return [$member, $invoice];
        });

        [$member, $invoice] = $member;

        $response = [
            'message' => $withInvoice ? 'Member and invoice created successfully.' : 'Member created successfully.',
            'data' => new MemberResource($member->load('package')),
        ];

        if ($invoice) {
            $response['invoice'] = new \App\Http\Resources\InvoiceResource($invoice);
        }

        return response()->json($response, 201);
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