# RetargetingPixelController

Manages retargeting pixel configurations for Facebook, Google, TikTok, LinkedIn, and Twitter.

## Location

`app/Http/Controllers/RetargetingPixelController.php`

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/retargeting-pixels` | `retargeting-pixels.index` | List pixels |
| POST | `/retargeting-pixels` | `retargeting-pixels.store` | Add pixel |
| PATCH | `/retargeting-pixels/{retargetingPixel}` | `retargeting-pixels.update` | Update pixel (status, name) |
| DELETE | `/retargeting-pixels/{retargetingPixel}` | `retargeting-pixels.destroy` | Remove pixel |

## Page

`resources/js/pages/retargeting-pixels/index.tsx`
