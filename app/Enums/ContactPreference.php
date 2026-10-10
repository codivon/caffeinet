<?php

namespace App\Enums;

/**
 * v39/v40/v41 — راه‌های ارتباطی پیشنهادی مشتری پس از پایان مهلت پخش بدون پذیرش اپراتور.
 *
 * v41 — مدل نهایی (درخواست مالک): همهٔ راه‌ها در «یک لیست واحد» چندانتخابی‌اند؛
 * «تماس تلفنی» گزینهٔ اول لیست است (دیگر چک‌باکس جدا نیست) و «فرقی ندارد» (any)
 * حذف شده. کاربر هر تعداد را که خواست تیک می‌زند. مقدار ذخیره‌شده در
 * orders.contact_preference ترکیبی است:
 *
 *      "call,telegram"      → تماس + چت تلگرام
 *      "call"               → فقط تماس تلفنی
 *      "telegram,whatsapp"  → چت تلگرام + واتس‌اپ
 *
 * مقادیر تک‌بخشی نسخهٔ v39 (call/app_chat/telegram/whatsapp/bale/eitaa/any)
 * و مدل v40 همچنان به‌درستی تفسیر می‌شوند (سازگاری با داده‌های موجود).
 */
enum ContactPreference: string
{
    case Call = 'call'; // تماس تلفنی (v41: گزینهٔ اول لیست واحد)
    case AppChat = 'app_chat'; // چت داخل خود برنامه
    case Telegram = 'telegram';
    case WhatsApp = 'whatsapp';
    case Bale = 'bale';
    case Eitaa = 'eitaa';
    case Any = 'any'; // فرقی ندارد (فقط برای تفسیر داده‌های قدیمی — از v41 در UI نیست)

    /** عنوان فارسی */
    public function label(): string
    {
        return match ($this) {
            self::Call => 'تماس تلفنی',
            self::AppChat => 'چت داخل برنامه',
            self::Telegram => 'تلگرام',
            self::WhatsApp => 'واتس‌اپ',
            self::Bale => 'بله',
            self::Eitaa => 'ایتا',
            self::Any => 'فرقی ندارد',
        };
    }

    /** توضیح کوتاه برای نمایش زیر گزینه (v40) */
    public function description(): string
    {
        return match ($this) {
            self::Call => 'کارشناس ما مستقیماً با شما تماس می‌گیرد',
            self::AppChat => 'گفتگو در همین برنامه و همین سفارش',
            self::Telegram => 'پیام از طریق تلگرام',
            self::WhatsApp => 'پیام از طریق واتس‌اپ',
            self::Bale => 'پیام از طریق بله',
            self::Eitaa => 'پیام از طریق ایتا',
            self::Any => 'هر راهی که برای ما راحت‌تر باشد',
        };
    }

    /** ایموجی/آیکون نمایشی */
    public function icon(): string
    {
        return match ($this) {
            self::Call => '📞',
            self::AppChat => '💬',
            self::Telegram => '✈️',
            self::WhatsApp => '🟢',
            self::Bale => '🔵',
            self::Eitaa => '📨',
            self::Any => '🤝',
        };
    }

    /** همهٔ مقادیر مجاز برای اعتبارسنجی */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }

    /** گزینه‌های بخش «چت» (مدل قدیمی v40 — برای سازگاری) */
    public static function chatOptions(): array
    {
        return [self::AppChat, self::Telegram, self::WhatsApp, self::Bale, self::Eitaa, self::Any];
    }

    /**
     * v41 — گزینه‌های لیست واحد چندانتخابی، به ترتیب نمایش:
     * تماس تلفنی اول، سپس راه‌های چت؛ «فرقی ندارد» حذف شده است.
     *
     * @return array<int, self>
     */
    public static function options(): array
    {
        return [self::Call, self::AppChat, self::Telegram, self::WhatsApp, self::Bale, self::Eitaa];
    }

    /**
     * v41 — ساخت مقدار ترکیبی از آرایهٔ توکن‌های انتخابی (مرتب‌سازی کانونی).
     * توکن‌های نامعتبر حذف می‌شوند؛ «any» هم به‌عنوان دادهٔ قدیمی می‌ماند.
     */
    public static function fromTokens(array $tokens): string
    {
        $valid = self::values();
        $tokens = array_values(array_unique(array_filter(array_map(
            fn ($t) => trim((string) $t),
            $tokens
        ), fn ($t) => $t !== '' && in_array($t, $valid, true))));

        /* مرتب‌سازی کانونی: مطابق ترتیب options() — تماس تلفنی اول */
        $order = array_map(fn (self $c) => $c->value, self::options());
        usort($tokens, fn ($a, $b) => array_search($a, $order, true) <=> array_search($b, $order, true));

        return implode(',', $tokens) ?: self::Any->value;
    }

    /** مقدار ترکیبی از اجزا می‌سازد: call?,chat (مدل قدیمی v40 — برای سازگاری) */
    public static function fromParts(bool $call, ?string $chat): string
    {
        $tokens = [];
        if ($call) {
            $tokens[] = self::Call->value;
        }
        if ($chat && $chat !== self::Call->value) {
            $tokens[] = $chat;
        }

        return implode(',', $tokens) ?: self::Any->value;
    }

    /**
     * تجزیهٔ مقدار ذخیره‌شده → همهٔ توکن‌های انتخابی.
     * مقادیر قدیمی تک‌بخشی هم پشتیبانی می‌شوند.
     *
     * @return array{call: bool, chat: self|null, all: array<int, self>}
     */
    public static function parse(?string $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return ['call' => false, 'chat' => null, 'all' => []];
        }

        $tokens = array_filter(array_map('trim', explode(',', $value)));

        $call = false;
        $chat = null;
        $all = [];

        foreach ($tokens as $token) {
            if ($token === self::Call->value) {
                $call = true;
                $all[] = self::Call;
                continue;
            }
            try {
                $enum = self::from($token);
                $chat = $enum;
                $all[] = $enum;
            } catch (\ValueError) {
                // توکن ناشناخته — نادیده گرفته می‌شود
            }
        }

        return ['call' => $call, 'chat' => $chat, 'all' => $all];
    }

    /** شرح فارسی کامل ترکیب برای پنل ادمین (v41: همهٔ توکن‌ها) */
    public static function describe(?string $value): ?string
    {
        if (! $value || trim((string) $value) === '') {
            return null;
        }

        $labels = [];
        foreach (self::parse($value)['all'] as $enum) {
            $labels[] = $enum->icon().' '.$enum->label();
        }

        return $labels ? implode(' + ', $labels) : null;
    }
}
