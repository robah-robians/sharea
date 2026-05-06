# Share Hope Campaign Management System

## Overview
Share Hope manages **two distinct campaign types** with different purposes, workflows, and visibility:

---

## 1. FUNDRAISING CAMPAIGNS (Primary)
**Purpose**: Raise funds for specific NGO initiatives with measurable goals

### Characteristics
- **Goal-based**: Each campaign has a funding target (KSh amount)
- **Deadline-driven**: Campaigns have end dates
- **Progress tracking**: Real-time donation tracking with progress bars
- **NGO-assigned**: Admin assigns campaigns to verified NGOs
- **Public visibility**: Displayed on homepage (3 curated) and campaigns page (all active)
- **Donor interaction**: Donors can donate directly to these campaigns

### Management
- **Created by**: Admin only (via `campaigns_hub.php` → "Deploy New Initiative" tab)
- **Edited by**: Admin only (via `edit_campaign.php`)
- **Managed by**: Admin (activate/deactivate/terminate)
- **Viewed by**: 
  - Admins (full control in campaigns_hub)
  - NGOs (assigned campaigns in their dashboard - read-only)
  - Donors (public campaigns page)

### Admin Controls
- Create new fundraising campaign
- Assign to verified NGO
- Set funding goal and deadline
- Set category
- Upload cover image
- Edit campaign details
- Change status (active/completed/archived)
- Terminate campaign (archive or delete)

### Database Table
`campaigns` table with fields:
- `id`, `ngo_id`, `title`, `description`, `goal_amount`, `current_amount`
- `deadline`, `category_id`, `status`, `image_url`, `created_at`

---

## 2. AWARENESS CAMPAIGNS (Secondary)
**Purpose**: Broadcast messages, alerts, and educational content to the platform community

### Characteristics
- **Message-based**: No funding goals or donations
- **Date-ranged**: Optional start/end dates
- **Priority levels**: Urgent, High, Medium, Low
- **Audience-targeted**: Can target donors, NGOs, or both
- **Admin-authored**: Created and managed by admins only
- **Platform-wide**: Displayed in announcements carousel on homepage

### Management
- **Created by**: Admin only (via `awareness_campaigns.php` → "Launch New Campaign" tab)
- **Edited by**: Admin only (via `edit_awareness_campaign.php`)
- **Managed by**: Admin (activate/deactivate/delete)
- **Viewed by**: All users (in announcements carousel)

### Admin Controls
- Create new awareness campaign
- Set title and description
- Choose target audience (donors/NGOs/both)
- Select campaign type (awareness/fundraising drive/education/emergency/seasonal)
- Set priority level
- Set date range (optional)
- Add action link (optional)
- Upload cover image (optional)
- Activate/deactivate
- Delete campaign

### Database Table
`awareness_campaigns` table with fields:
- `id`, `title`, `description`, `target_audience`, `campaign_type`
- `priority`, `start_date`, `end_date`, `action_link`, `image_url`
- `is_active`, `created_by`, `created_at`

---

## User Access Matrix

| Action | Admin | NGO | Donor |
|--------|-------|-----|-------|
| **Fundraising Campaigns** | | | |
| Create | ✅ | ❌ | ❌ |
| Edit | ✅ | ❌ | ❌ |
| View (assigned) | ✅ | ✅ (read-only) | ✅ (public) |
| Donate | ✅ | ✅ | ✅ |
| See donor info | ✅ | ❌ | ❌ |
| **Awareness Campaigns** | | | |
| Create | ✅ | ❌ | ❌ |
| Edit | ✅ | ❌ | ❌ |
| View | ✅ | ✅ | ✅ |
| Activate/Deactivate | ✅ | ❌ | ❌ |
| Delete | ✅ | ❌ | ❌ |

---

## Admin Workflow

### Creating a Fundraising Campaign
1. Go to Admin Dashboard → Campaigns Hub
2. Click "Deploy New Initiative" tab
3. Select verified NGO
4. Enter campaign title, goal amount, deadline
5. Select category
6. Write description
7. Upload cover image
8. Click "Initialize Node Deployment"

### Creating an Awareness Campaign
1. Go to Admin Dashboard → Awareness Campaigns
2. Click "Launch New Campaign" tab
3. Enter title and description
4. Select target audience and campaign type
5. Set priority level
6. Set date range (optional)
7. Add action link (optional)
8. Upload cover image (optional)
9. Check "Activate immediately"
10. Click "Launch Campaign"

### Managing Campaigns
- **Fundraising**: campaigns_hub.php (System Performance tab)
- **Awareness**: awareness_campaigns.php (Active Broadcasts tab)

---

## Key Differences Summary

| Aspect | Fundraising | Awareness |
|--------|-------------|-----------|
| **Purpose** | Raise funds | Broadcast messages |
| **Has Goal** | Yes (KSh amount) | No |
| **Has Deadline** | Yes (required) | Optional |
| **Donations** | Yes | No |
| **Progress Tracking** | Yes (%) | No |
| **Priority Levels** | No | Yes (Urgent/High/Medium/Low) |
| **Target Audience** | All donors | Configurable |
| **Display Location** | Homepage + Campaigns page | Announcements carousel |
| **NGO Assignment** | Yes (required) | No |

---

## File Locations

### Fundraising Campaign Files
- Admin creation: `/admin/create_campaign.php`
- Admin editing: `/admin/edit_campaign.php`
- Admin management: `/admin/campaigns_hub.php`
- NGO view: `/ngo/dashboard.php`
- Public view: `/campaigns.php`, `/donate.php`
- Actions: `/actions/create_campaign_action.php`, `/actions/edit_campaign_action.php`

### Awareness Campaign Files
- Admin creation/management: `/admin/awareness_campaigns.php`
- Admin editing: `/admin/edit_awareness_campaign.php`
- Public view: `/index.php` (announcements carousel)
- Actions: Handled inline in awareness_campaigns.php

---

## Best Practices

1. **Use Fundraising Campaigns for**: Specific projects with measurable funding needs
2. **Use Awareness Campaigns for**: Platform announcements, alerts, educational content, emergency notices
3. **Keep campaigns organized**: Use consistent naming conventions
4. **Set realistic deadlines**: For fundraising campaigns, ensure deadlines are achievable
5. **Prioritize awareness**: Use urgent priority for time-sensitive announcements
6. **Monitor progress**: Regularly check campaign performance in the hub

---

## Troubleshooting

**Issue**: NGOs can't see their assigned campaigns
- **Solution**: Check if NGO is verified in admin panel

**Issue**: Awareness campaign not showing on homepage
- **Solution**: Ensure `is_active = 1` and check date range

**Issue**: Fundraising campaign not appearing on campaigns page
- **Solution**: Verify status is "active" and NGO is verified

**Issue**: Can't edit a campaign
- **Solution**: Only admins can edit. Check user role and campaign ID.
