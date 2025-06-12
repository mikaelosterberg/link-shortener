<?php

namespace Tests\Feature;

use App\Models\AbTest;
use App\Models\AbTestVariant;
use App\Models\Click;
use App\Models\GeoRule;
use App\Models\Link;
use App\Models\LinkGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkDeletionCascadeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and authenticate
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_deleting_link_cascades_to_all_related_data()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create([
            'group_id' => $group->id,
            'click_count' => 10,
        ]);

        // Create related data
        $clicks = Click::factory()->count(5)->create(['link_id' => $link->id]);
        
        $geoRule = GeoRule::factory()->create([
            'link_id' => $link->id,
            'match_type' => 'country',
            'match_values' => ['US', 'CA'],
            'redirect_url' => 'https://na.example.com',
        ]);

        $abTest = AbTest::factory()->create([
            'link_id' => $link->id,
            'name' => 'Test Campaign',
        ]);

        $variant1 = AbTestVariant::factory()->create([
            'ab_test_id' => $abTest->id,
            'name' => 'Variant A',
            'url' => 'https://a.example.com',
        ]);

        $variant2 = AbTestVariant::factory()->create([
            'ab_test_id' => $abTest->id,
            'name' => 'Variant B', 
            'url' => 'https://b.example.com',
        ]);

        // Verify all related data exists
        $this->assertEquals(5, Click::where('link_id', $link->id)->count());
        $this->assertEquals(1, GeoRule::where('link_id', $link->id)->count());
        $this->assertEquals(1, AbTest::where('link_id', $link->id)->count());
        $this->assertEquals(2, AbTestVariant::where('ab_test_id', $abTest->id)->count());

        // Delete the link
        $link->delete();

        // Verify all related data is cascaded and deleted
        $this->assertEquals(0, Click::where('link_id', $link->id)->count());
        $this->assertEquals(0, GeoRule::where('link_id', $link->id)->count());
        $this->assertEquals(0, AbTest::where('link_id', $link->id)->count());
        $this->assertEquals(0, AbTestVariant::where('ab_test_id', $abTest->id)->count());

        // Verify the link itself is deleted
        $this->assertNull(Link::find($link->id));
        
        // Verify the group still exists (should not be deleted)
        $this->assertNotNull(LinkGroup::find($group->id));
    }

    public function test_deleting_ab_test_cascades_to_variants()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create(['group_id' => $group->id]);

        $abTest = AbTest::factory()->create([
            'link_id' => $link->id,
            'name' => 'Standalone Test',
        ]);

        $variants = AbTestVariant::factory()->count(3)->create([
            'ab_test_id' => $abTest->id,
        ]);

        $this->assertEquals(3, AbTestVariant::where('ab_test_id', $abTest->id)->count());

        // Delete the A/B test (not the link)
        $abTest->delete();

        // Verify variants are cascaded and deleted
        $this->assertEquals(0, AbTestVariant::where('ab_test_id', $abTest->id)->count());
        
        // Verify link and group still exist
        $this->assertNotNull(Link::find($link->id));
        $this->assertNotNull(LinkGroup::find($group->id));
    }

    public function test_clicks_with_ab_test_variant_references_are_deleted()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create(['group_id' => $group->id]);

        $abTest = AbTest::factory()->create(['link_id' => $link->id]);
        $variant = AbTestVariant::factory()->create(['ab_test_id' => $abTest->id]);

        // Create clicks that reference the A/B test variant
        $clicksWithVariant = Click::factory()->count(3)->create([
            'link_id' => $link->id,
            'ab_test_variant_id' => $variant->id,
        ]);

        // Create clicks without variant reference
        $clicksWithoutVariant = Click::factory()->count(2)->create([
            'link_id' => $link->id,
            'ab_test_variant_id' => null,
        ]);

        $this->assertEquals(5, Click::where('link_id', $link->id)->count());
        $this->assertEquals(3, Click::where('ab_test_variant_id', $variant->id)->count());

        // Delete the link
        $link->delete();

        // All clicks should be deleted (regardless of variant reference)
        $this->assertEquals(0, Click::where('link_id', $link->id)->count());
        $this->assertEquals(0, Click::where('ab_test_variant_id', $variant->id)->count());
    }

    public function test_bulk_link_deletion_cascades_properly()
    {
        $group = LinkGroup::factory()->create();
        
        $link1 = Link::factory()->create(['group_id' => $group->id]);
        $link2 = Link::factory()->create(['group_id' => $group->id]);

        // Create related data for both links
        Click::factory()->count(3)->create(['link_id' => $link1->id]);
        Click::factory()->count(2)->create(['link_id' => $link2->id]);
        
        GeoRule::factory()->create(['link_id' => $link1->id]);
        GeoRule::factory()->create(['link_id' => $link2->id]);

        $abTest1 = AbTest::factory()->create(['link_id' => $link1->id]);
        $abTest2 = AbTest::factory()->create(['link_id' => $link2->id]);

        AbTestVariant::factory()->create(['ab_test_id' => $abTest1->id]);
        AbTestVariant::factory()->create(['ab_test_id' => $abTest2->id]);

        // Verify initial state
        $this->assertEquals(5, Click::whereIn('link_id', [$link1->id, $link2->id])->count());
        $this->assertEquals(2, GeoRule::whereIn('link_id', [$link1->id, $link2->id])->count());
        $this->assertEquals(2, AbTest::whereIn('link_id', [$link1->id, $link2->id])->count());

        // Bulk delete both links
        Link::whereIn('id', [$link1->id, $link2->id])->delete();

        // Verify all related data is cascaded and deleted
        $this->assertEquals(0, Click::whereIn('link_id', [$link1->id, $link2->id])->count());
        $this->assertEquals(0, GeoRule::whereIn('link_id', [$link1->id, $link2->id])->count());
        $this->assertEquals(0, AbTest::whereIn('link_id', [$link1->id, $link2->id])->count());
    }

    public function test_utm_data_is_deleted_with_clicks()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create(['group_id' => $group->id]);

        // Create clicks with UTM data
        Click::factory()->count(3)->create([
            'link_id' => $link->id,
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => 'spring2024',
        ]);

        $this->assertEquals(3, Click::where('utm_campaign', 'spring2024')->count());

        // Delete the link
        $link->delete();

        // Verify UTM data is also deleted
        $this->assertEquals(0, Click::where('utm_campaign', 'spring2024')->count());
    }

    public function test_deleting_group_does_not_cascade_to_links()
    {
        $group = LinkGroup::factory()->create();
        $link = Link::factory()->create(['group_id' => $group->id]);
        
        Click::factory()->count(2)->create(['link_id' => $link->id]);

        // Delete the group
        $group->delete();

        // Link should still exist (groups don't cascade delete to links)
        $this->assertNotNull(Link::find($link->id));
        $this->assertEquals(2, Click::where('link_id', $link->id)->count());
        
        // But the link's group_id should be null or handled appropriately
        $refreshedLink = Link::find($link->id);
        $this->assertNull($refreshedLink->group); // Relationship returns null
    }
}