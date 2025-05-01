<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Lead;
use App\Models\Segments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SegmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_segment(): void
    {
        $segment = Segments::factory()->create();

        $this->assertDatabaseHas('segments', [
            'id' => $segment->id,
            'name' => $segment->name,
            'description' => $segment->description,
            'is_active' => true,
        ]);
    }

    public function test_segment_has_leads(): void
    {
        $segment = Segments::factory()->create();
        $lead = Lead::factory()->forSegment($segment)->create();

        $this->assertTrue($segment->leads->contains($lead));
        $this->assertEquals(1, $segment->leads->count());
    }

    public function test_can_create_inactive_segment(): void
    {
        $segment = Segments::factory()->inactive()->create();

        $this->assertDatabaseHas('segments', [
            'id' => $segment->id,
            'is_active' => false,
        ]);
    }

    public function test_scope_active(): void
    {
        // Criar um segmento ativo
        $activeSegment = Segments::factory()->create();

        // Criar um segmento inativo
        Segments::factory()->inactive()->create();

        $activeSegments = Segments::active()->get();

        $this->assertEquals(1, $activeSegments->count());
        $this->assertTrue($activeSegments->contains($activeSegment));
    }

    public function test_scope_inactive(): void
    {
        // Criar um segmento ativo
        Segments::factory()->create();

        // Criar um segmento inativo
        $inactiveSegment = Segments::factory()->inactive()->create();

        $inactiveSegments = Segments::inactive()->get();

        $this->assertEquals(1, $inactiveSegments->count());
        $this->assertTrue($inactiveSegments->contains($inactiveSegment));
    }
}
