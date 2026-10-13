<?php

namespace Tests\Unit\Domain;

use App\Domain\Entities\Member;
use App\Domain\Entities\User;
use App\Domain\Entities\UserRole;
use App\Domain\Errors\InvalidValue;
use PHPUnit\Framework\TestCase;

class UserAndMemberTest extends TestCase
{
    public function test_username_is_trimmed_and_lowercased(): void
    {
        $user = User::create('user-1', 'Ana ', 'hash', UserRole::Librarian);

        $this->assertSame('ana', $user->username);
    }

    public function test_empty_username_is_rejected(): void
    {
        $this->expectException(InvalidValue::class);
        User::create('user-1', '   ', 'hash', UserRole::Librarian);
    }

    public function test_api_can_assign_librarian_role(): void
    {
        $this->assertSame(UserRole::Librarian, UserRole::assignableByApi('librarian'));
    }

    public function test_api_cannot_assign_admin_role(): void
    {
        $this->expectException(InvalidValue::class);
        UserRole::assignableByApi('admin');
    }

    public function test_member_requires_name_and_document(): void
    {
        $member = Member::create('member-1', ' Ana Pérez ', ' 1020304050 ');

        $this->assertSame('Ana Pérez', $member->fullName);
        $this->assertSame('1020304050', $member->documentNumber);

        $this->expectException(InvalidValue::class);
        Member::create('member-2', 'Luis', '  ');
    }
}
