<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Traits;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HasCommonAttributesTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_active(): void
    {
        // Criar um registro ativo
        $activeRecord = Company::factory()->create();

        // Criar um registro inativo
        Company::factory()->inactive()->create();

        $activeRecords = Company::active()->get();

        $this->assertEquals(1, $activeRecords->count());
        $this->assertTrue($activeRecords->contains($activeRecord));
    }

    public function test_scope_inactive(): void
    {
        // Criar um registro ativo
        Company::factory()->create();

        // Criar um registro inativo
        $inactiveRecord = Company::factory()->inactive()->create();

        $inactiveRecords = Company::inactive()->get();

        $this->assertEquals(1, $inactiveRecords->count());
        $this->assertTrue($inactiveRecords->contains($inactiveRecord));
    }

    public function test_is_active(): void
    {
        $activeRecord = Company::factory()->create();
        $inactiveRecord = Company::factory()->inactive()->create();

        $this->assertTrue($activeRecord->isActive());
        $this->assertFalse($inactiveRecord->isActive());
    }

    public function test_get_created_at_formated(): void
    {
        $record = Company::factory()->create();

        $this->assertEquals(
            $record->created_at->format('d/m/Y H:i:s'),
            $record->getCreatedAtFormated()
        );

        $this->assertEquals(
            $record->created_at->format('Y-m-d'),
            $record->getCreatedAtFormated('Y-m-d')
        );
    }

    public function test_get_updated_at_formated(): void
    {
        $record = Company::factory()->create();

        $this->assertEquals(
            $record->updated_at->format('d/m/Y H:i:s'),
            $record->getUpdatedAtFormated()
        );

        $this->assertEquals(
            $record->updated_at->format('Y-m-d'),
            $record->getUpdatedAtFormated('Y-m-d')
        );
    }
}
