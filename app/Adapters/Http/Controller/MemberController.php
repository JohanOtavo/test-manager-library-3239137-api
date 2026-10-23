<?php

declare(strict_types=1);

namespace App\Adapters\Http\Controller;

use App\Adapters\Http\Presenter\MemberPresenter;
use App\Adapters\Http\Presenter\PagePresenter;
use App\Application\Shared\Pagination;
use App\Application\UseCase\Member\CreateMemberService;
use App\Application\UseCase\Member\GetMemberService;
use App\Application\UseCase\Member\SearchMembersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-05 · /members. */
final readonly class MemberController
{
    public function __construct(
        private SearchMembersService $searchMembers,
        private GetMemberService $getMember,
        private CreateMemberService $createMember,
        private int $pageSizeMax,
        private int $pageSizeDefault,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = Pagination::of(
            $request->integer('page', 1),
            $request->integer('size', $this->pageSizeDefault),
            $this->pageSizeMax,
            $this->pageSizeDefault,
        );

        $result = $this->searchMembers->handle($request->query('q'), $page);

        return new JsonResponse(PagePresenter::of($result, MemberPresenter::many($result->items)));
    }

    public function show(string $member): JsonResponse
    {
        $found = $this->getMember->handle($member);

        return new JsonResponse(MemberPresenter::one($found['member'], $found['active_loans']));
    }

    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'full_name' => ['required', 'string', 'max:200'],
            'document_number' => ['required', 'string', 'max:30'],
        ]);

        $created = $this->createMember->handle($input['full_name'], $input['document_number']);

        return new JsonResponse(MemberPresenter::one($created), 201);
    }
}
