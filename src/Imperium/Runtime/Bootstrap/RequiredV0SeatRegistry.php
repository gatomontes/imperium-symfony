<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Bootstrap;

final class RequiredV0SeatRegistry
{
    public const SEATS = [
        [
            "office" => "conscription",
            "role" => "recruiter",
            "seat" => "conscription.recruiter",
        ],
        [
            "office" => "curia",
            "role" => "seneschal",
            "seat" => "curia.seneschal",
        ],
        [
            "office" => "curia",
            "role" => "chamberlain",
            "seat" => "curia.chamberlain",
        ],
        [
            "office" => "curia",
            "role" => "secretary",
            "seat" => "curia.secretary",
        ],
        [
            "office" => "foundry",
            "role" => "artificer",
            "seat" => "foundry.artificer",
        ],
        [
            "office" => "foundry",
            "role" => "adversarial-reviewer",
            "seat" => "foundry.reviewer.adversarial",
        ],
        [
            "office" => "garrison",
            "role" => "constable",
            "seat" => "garrison.constable",
        ],
        [
            "office" => "guildhall",
            "role" => "guildmaster",
            "seat" => "guildhall.guildmaster",
        ],
        [
            "office" => "guildhall",
            "role" => "committee-disciplinary-fit",
            "seat" => "guildhall.committee.disciplinary-fit",
        ],
        [
            "office" => "guildhall",
            "role" => "committee-composition",
            "seat" => "guildhall.committee.composition",
        ],
        [
            "office" => "guildhall",
            "role" => "committee-boundary-challenge",
            "seat" => "guildhall.committee.boundary-challenge",
        ],
        [
            "office" => "hagiography",
            "role" => "sanctographer",
            "seat" => "hagiography.sanctographer",
        ],
        [
            "office" => "laboratorium",
            "role" => "alchemist",
            "seat" => "laboratorium.alchemist",
        ],
        [
            "office" => "senate",
            "role" => "lord-speaker",
            "seat" => "senate.lord-speaker",
        ],
        ["office" => "senate", "role" => "bailiff", "seat" => "senate.bailiff"],
        [
            "office" => "senate",
            "role" => "senator-consistency",
            "seat" => "senate.committee.consistency",
        ],
        [
            "office" => "senate",
            "role" => "senator-governance",
            "seat" => "senate.committee.governance",
        ],
        [
            "office" => "senate",
            "role" => "senator-practice",
            "seat" => "senate.committee.practice",
        ],
        [
            "office" => "senate",
            "role" => "senator-security",
            "seat" => "senate.committee.security",
        ],
        [
            "office" => "studium",
            "role" => "chancellor",
            "seat" => "studium.chancellor",
        ],
    ];

    public static function all(): array
    {
        return self::SEATS;
    }
}
