<?php

namespace Tests\Fakes;

use App\Application\Ports\MemberRepository;
use App\Domain\Entities\Member;

final class InMemoryMemberRepository implements MemberRepository
{
    /** @var array<string, Member> */
    public array $members = [];

    public function findById(string $id): ?Member
    {
        return $this->members[$id] ?? null;
    }

    public function add(Member $member): void
    {
        $this->members[$member->id] = $member;
    }
}
