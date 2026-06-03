<?php

declare(strict_types=1);

namespace ZxImage\Enum;

use ZxImage\Plugin\Atmega;
use ZxImage\Plugin\Attributes;
use ZxImage\Plugin\Bmc4;
use ZxImage\Plugin\Bsc;
use ZxImage\Plugin\Bsp;
use ZxImage\Plugin\Chrd;
use ZxImage\Plugin\Flash;
use ZxImage\Plugin\FramePluginInterface;
use ZxImage\Plugin\Gigascreen;
use ZxImage\Plugin\Grf;
use ZxImage\Plugin\Hidden;
use ZxImage\Plugin\Lowresgs;
use ZxImage\Plugin\Mc;
use ZxImage\Plugin\Mlt;
use ZxImage\Plugin\Monochrome;
use ZxImage\Plugin\Multiartist;
use ZxImage\Plugin\Multicolor;
use ZxImage\Plugin\Multicolor4;
use ZxImage\Plugin\Nxi;
use ZxImage\Plugin\S80;
use ZxImage\Plugin\S81;
use ZxImage\Plugin\Sam2;
use ZxImage\Plugin\Sam3;
use ZxImage\Plugin\Sam4;
use ZxImage\Plugin\Sca;
use ZxImage\Plugin\Sl2;
use ZxImage\Plugin\Specscii;
use ZxImage\Plugin\Ssx;
use ZxImage\Plugin\SsxRaw;
use ZxImage\Plugin\Standard;
use ZxImage\Plugin\Stellar;
use ZxImage\Plugin\Sxg;
use ZxImage\Plugin\Timex81;
use ZxImage\Plugin\Timexhr;
use ZxImage\Plugin\Timexhrg;
use ZxImage\Plugin\Tricolor;
use ZxImage\Plugin\Ulaplus;
use ZxImage\Plugin\Zxevo;

enum PluginType: string
{
    case Atmega = 'atmega';
    case Attributes = 'attributes';
    case Bmc4 = 'bmc4';
    case Bsc = 'bsc';
    case Bsp = 'bsp';
    case Chrd = 'chrd';
    case ChrdAlias = 'chr$';
    case Flash = 'flash';
    case Gigascreen = 'gigascreen';
    case Grf = 'grf';
    case Hidden = 'hidden';
    case Lowresgs = 'lowresgs';
    case Mc = 'mc';
    case Mlt = 'mlt';
    case Monochrome = 'monochrome';
    case Multiartist = 'multiartist';
    case Mg1 = 'mg1';
    case Mg2 = 'mg2';
    case Mg4 = 'mg4';
    case Mg8 = 'mg8';
    case Multicolor = 'multicolor';
    case Multicolor4 = 'multicolor4';
    case Nxi = 'nxi';
    case S80 = 's80';
    case S81 = 's81';
    case Sam2 = 'sam2';
    case Sam3 = 'sam3';
    case Sam4 = 'sam4';
    case Sca = 'sca';
    case Sl2 = 'sl2';
    case Specscii = 'specscii';
    case Ssx = 'ssx';
    case SsxRaw = 'ssxraw';
    case Standard = 'standard';
    case Stellar = 'stellar';
    case Sxg = 'sxg';
    case Timex81 = 'timex81';
    case Timexhr = 'timexhr';
    case Timexhrg = 'timexhrg';
    case Tricolor = 'tricolor';
    case Ulaplus = 'ulaplus';
    case Zxevo = 'zxevo';

    /**
     * @return class-string<FramePluginInterface>
     */
    public function pluginClassName(): string
    {
        return match ($this) {
            self::Atmega => Atmega::class,
            self::Attributes => Attributes::class,
            self::Bmc4 => Bmc4::class,
            self::Bsc => Bsc::class,
            self::Bsp => Bsp::class,
            self::Chrd, self::ChrdAlias => Chrd::class,
            self::Flash => Flash::class,
            self::Gigascreen => Gigascreen::class,
            self::Grf => Grf::class,
            self::Hidden => Hidden::class,
            self::Lowresgs => Lowresgs::class,
            self::Mc => Mc::class,
            self::Mlt => Mlt::class,
            self::Monochrome => Monochrome::class,
            self::Multiartist, self::Mg1, self::Mg2, self::Mg4, self::Mg8 => Multiartist::class,
            self::Multicolor => Multicolor::class,
            self::Multicolor4 => Multicolor4::class,
            self::Nxi => Nxi::class,
            self::S80 => S80::class,
            self::S81 => S81::class,
            self::Sam2 => Sam2::class,
            self::Sam3 => Sam3::class,
            self::Sam4 => Sam4::class,
            self::Sca => Sca::class,
            self::Sl2 => Sl2::class,
            self::Specscii => Specscii::class,
            self::Ssx => Ssx::class,
            self::SsxRaw => SsxRaw::class,
            self::Standard => Standard::class,
            self::Stellar => Stellar::class,
            self::Sxg => Sxg::class,
            self::Timex81 => Timex81::class,
            self::Timexhr => Timexhr::class,
            self::Timexhrg => Timexhrg::class,
            self::Tricolor => Tricolor::class,
            self::Ulaplus => Ulaplus::class,
            self::Zxevo => Zxevo::class,
        };
    }

    public function usesGigascreenModeInHash(): bool
    {
        return match ($this) {
            self::Bsp,
            self::ChrdAlias,
            self::Gigascreen,
            self::Lowresgs,
            self::Mg1,
            self::Mg2,
            self::Mg4,
            self::Mg8,
            self::Multiartist,
            self::Stellar,
            self::Timexhrg,
            self::Tricolor => true,
            default => false,
        };
    }
}
