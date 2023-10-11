<?php declare(strict_types=1);

namespace Swag\Security\Components;

class State
{
    public const CONFIG_PREFIX = 'SwagPlatformSecurity.config.';

    /**
     * @var AbstractSecurityFix[]
     */
    public const KNOWN_ISSUES = [
        \Swag\Security\Fixes\NEXT9241\SecurityFix::class,
        \Swag\Security\Fixes\NEXT9240\SecurityFix::class,
        \Swag\Security\Fixes\NEXT9175\SecurityFix::class,
        \Swag\Security\Fixes\NEXT9242\SecurityFix::class,
        \Swag\Security\Fixes\NEXT9243\SecurityFix::class,
        \Swag\Security\Fixes\NEXT9569\SecurityFix::class,
        \Swag\Security\Fixes\NEXT10624\SecurityFix::class,
        \Swag\Security\Fixes\NEXT10909\SecurityFix::class,
        \Swag\Security\Fixes\NEXT10905\SecurityFix::class,
        \Swag\Security\Fixes\NEXT12230\SecurityFix::class,
        \Swag\Security\Fixes\NEXT9689\SecurityFix::class,
        \Swag\Security\Fixes\NEXT12359\SecurityFix::class,
        \Swag\Security\Fixes\NEXT13371\SecurityFix::class,
        \Swag\Security\Fixes\NEXT13247\SecurityFix::class,
        \Swag\Security\Fixes\NEXT12824\SecurityFix::class,
        \Swag\Security\Fixes\NEXT13664\SecurityFix::class,
        \Swag\Security\Fixes\NEXT13896\SecurityFix::class,
        \Swag\Security\Fixes\NEXT14482\SecurityFix::class,
        \Swag\Security\Fixes\NEXT14533\SecurityFix::class,
        \Swag\Security\Fixes\NEXT15183\SecurityFix::class,
        \Swag\Security\Fixes\NEXT14744\SecurityFix::class,
        \Swag\Security\Fixes\NEXT14871\SecurityFix::class,
        \Swag\Security\Fixes\NEXT14883\SecurityFix::class,
        \Swag\Security\Fixes\NEXT15669\SecurityFix::class,
        \Swag\Security\Fixes\NEXT15673\SecurityFix::class,
        \Swag\Security\Fixes\NEXT15681\SecurityFix::class,
        \Swag\Security\Fixes\NEXT16429\SecurityFix::class,
        \Swag\Security\Fixes\NEXT15675\SecurityFix::class,
        \Swag\Security\Fixes\NEXT17527\SecurityFix::class,
        \Swag\Security\Fixes\NEXT19276\SecurityFix::class,
        \Swag\Security\Fixes\NEXT19820\SecurityFix::class,
        \Swag\Security\Fixes\NEXT20309\SecurityFix::class,
        \Swag\Security\Fixes\NEXT20348\SecurityFix::class,
        \Swag\Security\Fixes\NEXT20305\SecurityFix::class,
        \Swag\Security\Fixes\NEXT21078\SecurityFix::class,
        \Swag\Security\Fixes\NEXT21034\SecurityFix::class,
        \Swag\Security\Fixes\NEXT23325\SecurityFix::class,
        \Swag\Security\Fixes\NEXT23464\SecurityFix::class,
        \Swag\Security\Fixes\NEXT23562\SecurityFix::class,
        \Swag\Security\Fixes\NEXT22891\SecurityFix::class,
        \Swag\Security\Fixes\NEXT24667\SecurityFix::class,
        \Swag\Security\Fixes\NEXT24679\SecurityFix::class,
        \Swag\Security\Fixes\PPI737\SecurityFix::class,
        \Swag\Security\Fixes\NEXT26140\SecurityFix::class,
    ];

    /**
     * @var AbstractSecurityFix[]
     */
    private $activeFixes;

    /**
     * @var AbstractSecurityFix[]
     */
    private $availableFixes;

    public function __construct(array $availableFixes, array $activeFixes)
    {
        $this->availableFixes = $availableFixes;
        $this->activeFixes = $activeFixes;
    }

    public function getActiveFixes(): array
    {
        return $this->activeFixes;
    }

    public function getAvailableFixes(): array
    {
        return $this->availableFixes;
    }

    public function isActive(string $ticket): bool
    {
        foreach ($this->getActiveFixes() as $validFix) {
            if ($validFix::getTicket() === $ticket) {
                return true;
            }
        }

        return false;
    }
}
