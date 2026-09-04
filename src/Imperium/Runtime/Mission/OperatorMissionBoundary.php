<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

/** Sole trusted local boundary translating an explicit Operator order into narrow custody. */
final class OperatorMissionBoundary
{
    public function accept(MissionDossier $dossier, int $at): AcceptedMission
    {
        $dossier->validateAt($at);
        $key = random_bytes(32);
        $provenance = $dossier->toArray()['authorization_provenance']['grant_id'];
        $capabilities = [];
        foreach ($dossier->toArray()['permitted_acts'] as $grant) {
            $unsigned = new MissionCapability(
                $dossier->missionId(), $dossier->identity(), $grant['action'], $grant['actor'],
                $grant['target'], $at, $dossier->expiresAt(), bin2hex(random_bytes(16)), $provenance, '',
            );
            $capabilities[] = new MissionCapability(
                $unsigned->missionId, $unsigned->dossierIdentity, $unsigned->action, $unsigned->actor,
                $unsigned->target, $unsigned->notBefore, $unsigned->expiresAt, $unsigned->nonce,
                $unsigned->authorizationProvenance, MissionCapabilityVerifier::signature($key, $unsigned),
            );
        }
        return new AcceptedMission($dossier, $capabilities, new MissionCapabilityVerifier($key));
    }
}
