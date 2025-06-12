<?php

namespace Tests\Feature;

use App\Models\Click;
use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClickDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and authenticate
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_reset_click_count_for_single_link()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'click_count' => 50,
        ]);

        // Create some click records
        Click::factory()->count(5)->create(['link_id' => $link->id]);

        // Reset click count (this only resets the count, not the records)
        $link->update(['click_count' => 0]);

        $this->assertEquals(0, $link->fresh()->click_count);
        $this->assertEquals(5, $link->clicks()->count()); // Records should still exist
    }

    public function test_can_delete_all_clicks_for_single_link()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'click_count' => 10,
        ]);

        // Create click records
        Click::factory()->count(10)->create(['link_id' => $link->id]);

        $this->assertEquals(10, $link->clicks()->count());

        // Delete all clicks and reset count
        $deletedCount = $link->clicks()->delete();
        $link->update(['click_count' => 0]);

        $this->assertEquals(10, $deletedCount);
        $this->assertEquals(0, $link->fresh()->click_count);
        $this->assertEquals(0, $link->clicks()->count());
    }

    public function test_can_delete_individual_click_record()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'click_count' => 5,
        ]);

        $clicks = Click::factory()->count(5)->create(['link_id' => $link->id]);
        $clickToDelete = $clicks->first();

        $this->assertEquals(5, $link->clicks()->count());

        // Delete one click and update count
        $clickToDelete->delete();
        $link->update(['click_count' => max(0, $link->click_count - 1)]);

        $this->assertEquals(4, $link->fresh()->click_count);
        $this->assertEquals(4, $link->clicks()->count());
    }

    public function test_can_delete_multiple_clicks_and_update_count()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'click_count' => 10,
        ]);

        $clicks = Click::factory()->count(10)->create(['link_id' => $link->id]);
        $clicksToDelete = $clicks->take(3);

        $this->assertEquals(10, $link->clicks()->count());

        // Delete 3 clicks and update count
        $deletedCount = $clicksToDelete->count();
        $clicksToDelete->each(fn ($click) => $click->delete());
        
        $currentCount = $link->click_count;
        $newCount = max(0, $currentCount - $deletedCount);
        $link->update(['click_count' => $newCount]);

        $this->assertEquals(7, $link->fresh()->click_count);
        $this->assertEquals(7, $link->clicks()->count());
    }

    public function test_click_count_cannot_go_below_zero()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'click_count' => 2,
        ]);

        // Create more click records than the count (inconsistent state)
        Click::factory()->count(5)->create(['link_id' => $link->id]);

        // Try to delete all 5 clicks when count is only 2
        $deletedCount = $link->clicks()->count();
        $link->clicks()->delete();
        
        $currentCount = $link->click_count;
        $newCount = max(0, $currentCount - $deletedCount);
        $link->update(['click_count' => $newCount]);

        $this->assertEquals(0, $link->fresh()->click_count); // Should not go negative
        $this->assertEquals(0, $link->clicks()->count());
    }

    public function test_bulk_delete_all_clicks_for_multiple_links()
    {
        $group = LinkGroup::factory()->create();
        
        $link1 = Link::factory()->create([
            'group_id' => $group->id,
            'click_count' => 5,
        ]);
        $link2 = Link::factory()->create([
            'group_id' => $group->id,
            'click_count' => 3,
        ]);

        // Create clicks for both links
        Click::factory()->count(5)->create(['link_id' => $link1->id]);
        Click::factory()->count(3)->create(['link_id' => $link2->id]);

        $links = collect([$link1, $link2]);
        $totalDeleted = 0;

        // Simulate bulk delete all clicks action
        foreach ($links as $link) {
            $deleted = $link->clicks()->delete();
            $totalDeleted += $deleted;
            $link->update(['click_count' => 0]);
        }

        $this->assertEquals(8, $totalDeleted);
        $this->assertEquals(0, $link1->fresh()->click_count);
        $this->assertEquals(0, $link2->fresh()->click_count);
        $this->assertEquals(0, $link1->clicks()->count());
        $this->assertEquals(0, $link2->clicks()->count());
    }

    public function test_bulk_reset_click_counts_only()
    {
        $group = LinkGroup::factory()->create();
        
        $link1 = Link::factory()->create([
            'group_id' => $group->id,
            'click_count' => 10,
        ]);
        $link2 = Link::factory()->create([
            'group_id' => $group->id,
            'click_count' => 15,
        ]);

        // Create clicks for both links
        Click::factory()->count(5)->create(['link_id' => $link1->id]);
        Click::factory()->count(8)->create(['link_id' => $link2->id]);

        $links = collect([$link1, $link2]);

        // Simulate bulk reset counts action (doesn't delete records)
        foreach ($links as $link) {
            $link->update(['click_count' => 0]);
        }

        $this->assertEquals(0, $link1->fresh()->click_count);
        $this->assertEquals(0, $link2->fresh()->click_count);
        $this->assertEquals(5, $link1->clicks()->count()); // Records still exist
        $this->assertEquals(8, $link2->clicks()->count()); // Records still exist
    }

    public function test_deleting_clicks_with_utm_data()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'click_count' => 3,
        ]);

        // Create clicks with UTM data
        Click::factory()->count(3)->create([
            'link_id' => $link->id,
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'spring2024',
        ]);

        $this->assertEquals(3, $link->clicks()->count());

        // Delete all clicks
        $link->clicks()->delete();
        $link->update(['click_count' => 0]);

        $this->assertEquals(0, $link->fresh()->click_count);
        $this->assertEquals(0, $link->clicks()->count());
        
        // Verify UTM data is also deleted
        $this->assertEquals(0, Click::whereNotNull('utm_source')->count());
    }
}