<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_view_route_works()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create([
            'user_id' => $user->id,
            'visibility' => 'public',
        ]);

        $this->actingAs($user)
            ->get(route('reports.view', $report))
            ->assertStatus(200)
            ->assertSee($report->name);
    }

    public function test_report_builder_route_works()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('reports.builder', $report))
            ->assertStatus(200)
            ->assertSee('Report Builder')
            ->assertSee($report->name);
    }

    public function test_report_data_api_returns_json()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create([
            'user_id' => $user->id,
            'visibility' => 'public',
        ]);

        $this->actingAs($user)
            ->get(route('reports.data', $report))
            ->assertStatus(200)
            ->assertJsonStructure([
                'components',
                'dateRange',
                'totalComponents',
            ]);
    }

    public function test_unauthorized_user_cannot_edit_report()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $owner->id,
            'visibility' => 'private',
        ]);

        $this->actingAs($otherUser)
            ->get(route('reports.builder', $report))
            ->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_view_private_report()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $owner->id,
            'visibility' => 'private',
        ]);

        $this->actingAs($otherUser)
            ->get(route('reports.view', $report))
            ->assertStatus(403);
    }

    public function test_can_update_report_components()
    {
        $user = User::factory()->create();
        $report = Report::factory()->create(['user_id' => $user->id]);

        $componentData = [
            'components' => [
                [
                    'component_type' => 'metric_card',
                    'title' => 'Test Metric',
                    'config' => ['metric' => 'total_clicks'],
                    'position_x' => 0,
                    'position_y' => 0,
                    'width' => 4,
                    'height' => 2,
                    'order_index' => 0,
                ],
            ],
        ];

        $this->actingAs($user)
            ->postJson(route('reports.update-components', $report), $componentData)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('report_components', [
            'report_id' => $report->id,
            'component_type' => 'metric_card',
            'title' => 'Test Metric',
        ]);
    }
}
