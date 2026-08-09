<?php

declare(strict_types=1);

namespace App\Bootstrap;

final readonly class MasterMason
{
    public function __construct(private StateStore $store)
    {
    }

    public function executeThroughRouteVerification(ValidationReceipt $receipt, string $instanceId): array
    {
        return $this->store->locked(function () use ($receipt, $instanceId): array {
            $record = $this->store->read();
            if (null !== $record) {
                $this->assertSameBinding($record, $receipt, $instanceId);
            } else {
                $record = $this->initialRecord($receipt, $instanceId);
            }

            $state = BootstrapState::from($record['state']);
            if (BootstrapState::Uninitialized === $state) {
                $record = $this->transition($record, 'T01', BootstrapState::ManifestBound, [
                    'binding_digest' => hash('sha256', CanonicalJson::encode($record['binding'])),
                ]);
            }
            if (BootstrapState::ManifestBound === BootstrapState::from($record['state'])) {
                $record = $this->transition($record, 'T02', BootstrapState::ConscriptionActive, [
                    'runtime_id' => $instanceId.'.office.conscription',
                    'mode' => 'mechanical-interface-only',
                ]);
            }
            if (BootstrapState::ConscriptionActive === BootstrapState::from($record['state'])) {
                $record = $this->transition($record, 'T03', BootstrapState::ProvisionalRecruiterBound, [
                    'manifestation_id' => $instanceId.'.officer.provisional-recruiter.1',
                    'seat' => 'conscription.recruiter',
                    'occupancy_generation' => 1,
                    'authority' => 'succession-only',
                ]);
            }
            if (BootstrapState::ProvisionalRecruiterBound === BootstrapState::from($record['state'])) {
                $record = $this->installOrdinaryRecruiter($record, $receipt, $instanceId);
            }
            if (BootstrapState::OrdinaryRecruiterBound === BootstrapState::from($record['state'])) {
                $record = $this->assembleSecretaryAndRector($record, $receipt, $instanceId);
            }
            if (BootstrapState::TriadAssembled === BootstrapState::from($record['state'])) {
                $record = $this->activatePrimordialOffices($record, $receipt, $instanceId);
            }
            if (BootstrapState::OfficesActive === BootstrapState::from($record['state'])) {
                $record = $this->bindPrimordialOfficers($record, $instanceId);
            }
            if (BootstrapState::TriadBoundInactive === BootstrapState::from($record['state'])) {
                $record = $this->verifyPrimordialRoutes($record, $receipt, $instanceId);
            }

            return $record;
        });
    }

    private function verifyPrimordialRoutes(array $record, ValidationReceipt $receipt, string $instanceId): array
    {
        $t07 = $this->lastSuccessfulOutput($record, 'T07');
        $bindings = $t07['bindings'] ?? null;
        $runtimes = $t07['runtimes'] ?? null;
        if (!is_array($bindings)
            || !is_array($runtimes)
            || true !== ($t07['binding_atomic'] ?? null)
            || false !== ($t07['officers_active'] ?? null)
            || false !== ($t07['offices_addressable'] ?? null)
        ) {
            throw new \RuntimeException('B71_ENDPOINT_MISMATCH: atomic T07 bindings are absent.');
        }

        $endpoints = [
            'secretariat.secretary' => ['role' => 'secretary', 'office' => 'secretariat'],
            'castellan.rector' => ['role' => 'rector', 'office' => 'castellan'],
        ];
        $resolved = [];
        foreach ($endpoints as $endpoint => $specification) {
            $binding = $bindings[$specification['role']] ?? null;
            $runtime = $runtimes[$specification['office']] ?? null;
            $occupant = is_array($runtime) ? ($runtime['occupant'] ?? null) : null;
            if (!is_array($binding)
                || !is_array($runtime)
                || !is_array($occupant)
                || $endpoint !== ($binding['seat'] ?? null)
                || 1 !== ($binding['occupancy_generation'] ?? null)
                || $instanceId.'.office.'.$specification['office'] !== ($runtime['runtime_id'] ?? null)
                || 'active-with-inactive-occupant' !== ($runtime['mode'] ?? null)
                || false !== ($runtime['addressable'] ?? null)
                || $endpoint !== ($runtime['resident_seat'] ?? null)
                || $endpoint !== ($occupant['seat'] ?? null)
                || 1 !== ($occupant['occupancy_generation'] ?? null)
                || 'bound-inactive' !== ($occupant['status'] ?? null)
                || $occupant !== ($binding['occupant'] ?? null)
            ) {
                throw new \RuntimeException('B71_ENDPOINT_MISMATCH: '.$endpoint.' does not resolve to its exact inactive T07 occupant.');
            }
            $resolved[$endpoint] = [
                'runtime_id' => $runtime['runtime_id'],
                'manifestation_id' => $occupant['manifestation_id'],
                'occupancy_generation' => $occupant['occupancy_generation'],
                'status' => $occupant['status'],
            ];
        }

        $routeRecord = $receipt->manifest['unsigned_payload']['primordial']['routes'] ?? null;
        $routeArtifact = $receipt->routes;
        $expectedRoutes = [
            ['from' => 'secretariat.secretary', 'to' => 'castellan.rector'],
            ['from' => 'castellan.rector', 'to' => 'secretariat.secretary'],
        ];
        $expectedProbeChecks = [
            'exact-endpoint-resolution',
            'bound-occupancy-generation',
            'no-office-work-delivery',
        ];
        $routeArtifactKeys = array_keys($routeArtifact);
        sort($routeArtifactKeys, SORT_STRING);
        $expectedRouteArtifactKeys = ['charter_generation', 'default', 'probe_checks', 'routes', 'schema', 'version'];
        if (!is_array($routeRecord)
            || !isset($routeRecord['version'], $routeRecord['digest'])
            || '1.0.0' !== $routeRecord['version']
            || 'imperium.primordial-routes/v1' !== ($routeArtifact['schema'] ?? null)
            || '1.0.0' !== ($routeArtifact['version'] ?? null)
            || $receipt->charterGeneration !== ($routeArtifact['charter_generation'] ?? null)
            || 'closed' !== ($routeArtifact['default'] ?? null)
            || $expectedRoutes !== ($routeArtifact['routes'] ?? null)
            || $expectedProbeChecks !== ($routeArtifact['probe_checks'] ?? null)
            || $expectedRouteArtifactKeys !== $routeArtifactKeys
        ) {
            throw new \RuntimeException('B70_ROUTE_MISMATCH: pinned primordial route declaration is invalid.');
        }
        if ($this->containsEnabledRoute($record)) {
            throw new \RuntimeException('B72_UNDECLARED_ROUTE: a route is already open before T08.');
        }

        $configuredRoutes = [];
        $probes = [];
        foreach ($expectedRoutes as $index => $route) {
            $source = $resolved[$route['from']] ?? null;
            $target = $resolved[$route['to']] ?? null;
            if (!is_array($source) || !is_array($target)) {
                throw new \RuntimeException('B73_ROUTE_PROBE_FAILED: a declared endpoint could not be probed.');
            }
            $configuredRoutes[] = [
                'from' => $route['from'],
                'to' => $route['to'],
                'enabled' => false,
            ];
            $probes[] = [
                'probe_id' => 'primordial-route-probe.'.($index + 1),
                'from' => $route['from'],
                'to' => $route['to'],
                'source_manifestation_id' => $source['manifestation_id'],
                'source_occupancy_generation' => $source['occupancy_generation'],
                'target_manifestation_id' => $target['manifestation_id'],
                'target_occupancy_generation' => $target['occupancy_generation'],
                'checks' => [
                    'exact-endpoint-resolution' => true,
                    'bound-occupancy-generation' => true,
                    'no-office-work-delivery' => true,
                ],
                'work_delivered' => false,
                'result' => 'PASS',
            ];
        }

        $configuration = [
            'artifact_version' => $routeRecord['version'],
            'artifact_digest' => $routeRecord['digest'],
            'default' => 'closed',
            'status' => 'verified-disabled',
            'routes' => $configuredRoutes,
        ];

        return $this->transition($record, 'T08', BootstrapState::RoutesVerified, [
            'route_configuration' => $configuration,
            'route_configuration_digest' => hash('sha256', CanonicalJson::encode($configuration)),
            'endpoint_bindings' => $resolved,
            'probes' => $probes,
            'all_probes_passed' => true,
            'work_delivered' => false,
            'routes_enabled' => false,
            'officers_active' => false,
            'offices_addressable' => false,
        ]);
    }

    private function containsEnabledRoute(array $record): bool
    {
        $contains = function (mixed $value) use (&$contains): bool {
            if (!is_array($value)) {
                return false;
            }
            if (true === ($value['enabled'] ?? null)) {
                return true;
            }
            foreach ($value as $nested) {
                if ($contains($nested)) {
                    return true;
                }
            }

            return false;
        };

        return $contains($record['events'] ?? []);
    }

    private function bindPrimordialOfficers(array $record, string $instanceId): array
    {
        $t05 = $this->lastSuccessfulOutput($record, 'T05');
        $t06 = $this->lastSuccessfulOutput($record, 'T06');
        $packets = $t05['delivery_packets'] ?? null;
        $reservations = $t05['seat_reservations'] ?? null;
        $runtimes = $t06['runtimes'] ?? null;
        if (!is_array($packets)
            || !is_array($reservations)
            || !is_array($runtimes)
            || true !== ($t05['atomic_pair'] ?? null)
            || true !== ($t06['activation_atomic'] ?? null)
        ) {
            throw new \RuntimeException('B60_BINDING_INPUT_MISMATCH: atomic T05/T06 inputs are absent.');
        }

        $specifications = [
            'secretary' => ['seat' => 'secretariat.secretary', 'office' => 'secretariat'],
            'rector' => ['seat' => 'castellan.rector', 'office' => 'castellan'],
        ];
        $bindings = [];
        $boundRuntimes = $runtimes;
        foreach ($specifications as $role => $specification) {
            $packet = $packets[$role] ?? null;
            $reservation = $reservations[$role] ?? null;
            $runtime = $runtimes[$specification['office']] ?? null;
            if (!is_array($packet)
                || !is_array($reservation)
                || !is_array($runtime)
                || true !== ($packet['sealed'] ?? null)
                || 'qualified-unbound' !== ($packet['candidate']['status'] ?? null)
                || $specification['seat'] !== ($packet['candidate']['target_seat'] ?? null)
                || 1 !== ($packet['candidate']['target_occupancy_generation'] ?? null)
                || $specification['seat'] !== ($reservation['seat'] ?? null)
                || 0 !== ($reservation['expected_generation'] ?? null)
                || 'HELD' !== ($reservation['status'] ?? null)
                || $instanceId.'.office.'.$specification['office'] !== ($runtime['runtime_id'] ?? null)
                || 'active-but-unavailable' !== ($runtime['mode'] ?? null)
                || false !== ($runtime['addressable'] ?? null)
                || $specification['seat'] !== ($runtime['resident_seat'] ?? null)
                || !array_key_exists('occupant', $runtime)
                || null !== $runtime['occupant']
            ) {
                throw new \RuntimeException('B61_BINDING_PRECONDITION_FAILED: '.$role.' binding inputs are invalid.');
            }

            $packetDigest = $packet['packet_digest'] ?? null;
            $digestiblePacket = $packet;
            unset($digestiblePacket['packet_digest']);
            if (!is_string($packetDigest)
                || !hash_equals($packetDigest, hash('sha256', CanonicalJson::encode($digestiblePacket)))
                || !hash_equals($packetDigest, (string) ($t06['delivery_packets_preserved'][$role] ?? ''))
            ) {
                throw new \RuntimeException('B62_PACKET_SEAL_INVALID: '.$role.' packet is not the preserved T05 packet.');
            }

            $occupant = [
                'manifestation_id' => $packet['candidate']['manifestation_id'],
                'seat' => $specification['seat'],
                'occupancy_generation' => 1,
                'status' => 'bound-inactive',
                'source_packet_digest' => $packetDigest,
            ];
            $boundRuntimes[$specification['office']]['occupant'] = $occupant;
            $boundRuntimes[$specification['office']]['mode'] = 'active-with-inactive-occupant';
            $boundRuntimes[$specification['office']]['addressable'] = false;
            $bindings[$role] = [
                'seat' => $specification['seat'],
                'prior_occupancy_generation' => 0,
                'occupancy_generation' => 1,
                'occupant' => $occupant,
                'packet_disposition' => 'consumed-by-binding',
            ];
        }

        return $this->transition($record, 'T07', BootstrapState::TriadBoundInactive, [
            'bindings' => $bindings,
            'runtimes' => $boundRuntimes,
            'binding_atomic' => true,
            'officers_active' => false,
            'offices_addressable' => false,
        ]);
    }

    private function activatePrimordialOffices(array $record, ValidationReceipt $receipt, string $instanceId): array
    {
        $t05 = $this->lastSuccessfulOutput($record, 'T05');
        $packets = $t05['delivery_packets'] ?? null;
        $reservations = $t05['seat_reservations'] ?? null;
        if (!is_array($packets) || !is_array($reservations) || true !== ($t05['atomic_pair'] ?? null)) {
            throw new \RuntimeException('B51_PACKET_STALE: T05 did not produce an atomic delivery pair.');
        }

        $specifications = [
            'secretary' => ['seat' => 'secretariat.secretary', 'office' => 'secretariat'],
            'rector' => ['seat' => 'castellan.rector', 'office' => 'castellan'],
        ];
        foreach ($specifications as $role => $specification) {
            $packet = $packets[$role] ?? null;
            $reservation = $reservations[$role] ?? null;
            if (!is_array($packet)
                || !is_array($reservation)
                || true !== ($packet['sealed'] ?? null)
                || 'qualified-unbound' !== ($packet['candidate']['status'] ?? null)
                || $specification['seat'] !== ($packet['candidate']['target_seat'] ?? null)
                || 1 !== ($packet['candidate']['target_occupancy_generation'] ?? null)
                || $specification['seat'] !== ($reservation['seat'] ?? null)
                || 0 !== ($reservation['expected_generation'] ?? null)
                || 'HELD' !== ($reservation['status'] ?? null)
            ) {
                throw new \RuntimeException('B51_PACKET_STALE: '.$role.' packet or Seat reservation is invalid.');
            }

            $packetDigest = $packet['packet_digest'] ?? null;
            $digestiblePacket = $packet;
            unset($digestiblePacket['packet_digest']);
            if (!is_string($packetDigest)
                || !hash_equals($packetDigest, hash('sha256', CanonicalJson::encode($digestiblePacket)))
            ) {
                throw new \RuntimeException('B51_PACKET_STALE: '.$role.' packet seal is invalid.');
            }
        }

        $manifestOffices = $receipt->manifest['unsigned_payload']['primordial']['offices'] ?? null;
        if (!is_array($manifestOffices)) {
            throw new \RuntimeException('B50_OFFICE_MISMATCH: pinned Office definitions are absent.');
        }

        $runtimes = [];
        foreach ($specifications as $specification) {
            $office = $specification['office'];
            $definition = $manifestOffices[$office] ?? null;
            if (!is_array($definition)
                || !isset($definition['artifact'], $definition['version'], $definition['digest'])
            ) {
                throw new \RuntimeException('B50_OFFICE_MISMATCH: pinned '.$office.' definition is invalid.');
            }
            $runtimeId = $instanceId.'.office.'.$office;
            if ($this->outputContainsRuntime($record, $runtimeId)) {
                throw new \RuntimeException('B52_OFFICE_EXISTS: '.$office.' runtime already exists.');
            }
            $runtimes[$office] = [
                'runtime_id' => $runtimeId,
                'definition' => [
                    'artifact' => $definition['artifact'],
                    'version' => $definition['version'],
                    'digest' => $definition['digest'],
                ],
                'charter_generation' => $receipt->charterGeneration,
                'mode' => 'active-but-unavailable',
                'addressable' => false,
                'resident_seat' => $specification['seat'],
                'occupant' => null,
            ];
        }

        return $this->transition($record, 'T06', BootstrapState::OfficesActive, [
            'runtimes' => $runtimes,
            'activation_atomic' => true,
            'delivery_packets_preserved' => [
                'secretary' => $packets['secretary']['packet_digest'],
                'rector' => $packets['rector']['packet_digest'],
            ],
        ]);
    }

    private function outputContainsRuntime(array $record, string $runtimeId): bool
    {
        $contains = function (mixed $value) use (&$contains, $runtimeId): bool {
            if (!is_array($value)) {
                return false;
            }
            if (($value['runtime_id'] ?? null) === $runtimeId) {
                return true;
            }
            foreach ($value as $nested) {
                if ($contains($nested)) {
                    return true;
                }
            }
            return false;
        };

        return $contains($record['events'] ?? []);
    }

    private function assembleSecretaryAndRector(array $record, ValidationReceipt $receipt, string $instanceId): array
    {
        $t04 = $this->lastSuccessfulOutput($record, 'T04');
        $recruiter = $t04['successor'] ?? null;
        if (!is_array($recruiter)
            || ($recruiter['seat'] ?? null) !== 'conscription.recruiter'
            || ($recruiter['authority'] ?? null) !== 'ordinary-recruiter'
            || ($recruiter['occupancy_generation'] ?? null) !== 2
        ) {
            throw new \RuntimeException('B40_RECRUITER_CHANGED: T04 occupancy no longer matches its receipt.');
        }

        $manifestCommissions = $receipt->manifest['unsigned_payload']['primordial']['assembly_commissions'] ?? null;
        if (!is_array($manifestCommissions)) {
            throw new \RuntimeException('B41_COMMISSION_MISMATCH: no pinned assembly commissions are present.');
        }

        $specifications = [
            'secretary' => [
                'commission' => $receipt->secretaryCommission,
                'id' => 'primordial.secretary-assembly.1',
                'seat' => 'secretariat.secretary',
                'profile' => 'secretariat.secretary@1.0.0',
                'substrate' => 'generic-officer.secretary@1.0.0',
                'qualification' => 'qualification.secretariat.secretary.v1',
            ],
            'rector' => [
                'commission' => $receipt->rectorCommission,
                'id' => 'primordial.rector-assembly.1',
                'seat' => 'castellan.rector',
                'profile' => 'castellan.rector@1.0.0',
                'substrate' => 'generic-officer.rector@1.0.0',
                'qualification' => 'qualification.castellan.rector.v1',
            ],
        ];

        $packets = [];
        foreach ($specifications as $role => $specification) {
            $commission = $specification['commission'];
            $observed = [
                $commission['id'] ?? null,
                $commission['single_use'] ?? null,
                $commission['charter_generation'] ?? null,
                $commission['issuer']['seat'] ?? null,
                $commission['issuer']['source_transition'] ?? null,
                $commission['issuer']['occupancy_generation'] ?? null,
                $commission['target']['seat'] ?? null,
                $commission['target']['profile'] ?? null,
                $commission['target']['substrate'] ?? null,
                $commission['target']['qualification_contract'] ?? null,
                $commission['constraints']['expected_occupancy_generation'] ?? null,
                $commission['constraints']['paired_commission'] ?? null,
                $commission['constraints']['same_attempt_required'] ?? null,
            ];
            $expected = [
                $specification['id'], true, 'charter-development-1', 'conscription.recruiter',
                'T04.successor', 2, $specification['seat'], $specification['profile'],
                $specification['substrate'], $specification['qualification'], 0,
                'secretary' === $role ? 'primordial.rector-assembly.1' : 'primordial.secretary-assembly.1', true,
            ];
            if ($observed !== $expected || !isset($manifestCommissions[$role]['digest'])) {
                throw new \RuntimeException('B41_COMMISSION_MISMATCH: pinned '.$role.' commission contents are invalid.');
            }

            $candidateId = $instanceId.'.officer.'.$role.'.1';
            $qualification = [
                'commission_id' => $specification['id'],
                'commission_digest' => $manifestCommissions[$role]['digest'],
                'candidate_id' => $candidateId,
                'profile' => $specification['profile'],
                'substrate' => $specification['substrate'],
                'qualification_contract' => $specification['qualification'],
                'checks' => [
                    'exact_profile_installation' => true,
                    'declared_authority_restraint' => true,
                    'version_and_provenance_preservation' => true,
                ],
            ];
            $packet = [
                'commission' => ['id' => $specification['id'], 'digest' => $manifestCommissions[$role]['digest'], 'consumed' => true],
                'candidate' => ['manifestation_id' => $candidateId, 'target_seat' => $specification['seat'], 'target_occupancy_generation' => 1, 'status' => 'qualified-unbound'],
                'qualification' => $qualification,
                'qualification_digest' => hash('sha256', CanonicalJson::encode($qualification)),
                'sealed' => true,
            ];
            $packet['packet_digest'] = hash('sha256', CanonicalJson::encode($packet));
            $packets[$role] = $packet;
        }

        return $this->transition($record, 'T05', BootstrapState::TriadAssembled, [
            'recruiter_manifestation_id' => $recruiter['manifestation_id'],
            'assembly_attempt' => 1,
            'seat_reservations' => [
                'secretary' => ['seat' => 'secretariat.secretary', 'expected_generation' => 0, 'status' => 'HELD'],
                'rector' => ['seat' => 'castellan.rector', 'expected_generation' => 0, 'status' => 'HELD'],
            ],
            'delivery_packets' => $packets,
            'atomic_pair' => true,
        ]);
    }

    private function installOrdinaryRecruiter(array $record, ValidationReceipt $receipt, string $instanceId): array
    {
        $provisional = $this->lastSuccessfulOutput($record, 'T03');
        if (
            ($provisional['seat'] ?? null) !== 'conscription.recruiter'
            || ($provisional['authority'] ?? null) !== 'succession-only'
            || ($provisional['occupancy_generation'] ?? null) !== 1
        ) {
            throw new \RuntimeException('B30_PROVISIONAL_RECRUITER_CHANGED: T03 occupancy no longer matches its receipt.');
        }

        $commissionRecord = $receipt->manifest['unsigned_payload']['primordial']['succession_commission'] ?? null;
        $commission = $receipt->successionCommission;
        if (!is_array($commissionRecord) || !isset($commissionRecord['digest'])) {
            throw new \RuntimeException('B31_SUCCESSION_COMMISSION_MISMATCH: no pinned commission is present.');
        }

        $commissionId = 'primordial.recruiter-succession.1';
        $expectedCommission = [
            $commissionId,
            true,
            'charter-development-1',
            'conscription.recruiter',
            'conscription.recruiter.ordinary@1.0.0',
            'generic-officer.ordinary-recruiter@1.0.0',
            'qualification.conscription.recruiter.ordinary.v1',
            1,
        ];
        $observedCommission = [
            $commission['id'] ?? null,
            $commission['single_use'] ?? null,
            $commission['charter_generation'] ?? null,
            $commission['target']['seat'] ?? null,
            $commission['target']['profile'] ?? null,
            $commission['target']['substrate'] ?? null,
            $commission['target']['qualification_contract'] ?? null,
            $commission['constraints']['expected_occupancy_generation'] ?? null,
        ];
        if ($expectedCommission !== $observedCommission) {
            throw new \RuntimeException('B31_SUCCESSION_COMMISSION_MISMATCH: pinned commission contents are invalid.');
        }

        $successorId = $instanceId.'.officer.ordinary-recruiter.1';
        if ($successorId === ($provisional['manifestation_id'] ?? null)) {
            throw new \RuntimeException('B32_SUCCESSOR_QUALIFICATION_FAILED: successor identity is not distinct.');
        }

        $qualificationPacket = [
            'commission_id' => $commissionId,
            'commission_digest' => $commissionRecord['digest'],
            'candidate_id' => $successorId,
            'profile' => 'conscription.recruiter.ordinary@1.0.0',
            'substrate' => 'generic-officer.ordinary-recruiter@1.0.0',
            'qualification_contract' => 'qualification.conscription.recruiter.ordinary.v1',
            'checks' => [
                'exact_profile_installation' => true,
                'declared_authority_restraint' => true,
                'version_and_provenance_preservation' => true,
            ],
        ];

        return $this->transition($record, 'T04', BootstrapState::OrdinaryRecruiterBound, [
            'commission' => [
                'id' => $commissionId,
                'digest' => $commissionRecord['digest'],
                'consumed' => true,
            ],
            'retired' => [
                'manifestation_id' => $provisional['manifestation_id'],
                'seat' => $provisional['seat'],
                'occupancy_generation' => $provisional['occupancy_generation'],
                'disposition' => 'retired-after-succession',
            ],
            'successor' => [
                'manifestation_id' => $successorId,
                'seat' => 'conscription.recruiter',
                'occupancy_generation' => 2,
                'authority' => 'ordinary-recruiter',
                'predecessor' => $provisional['manifestation_id'],
            ],
            'qualification_packet' => $qualificationPacket,
            'qualification_packet_digest' => hash('sha256', CanonicalJson::encode($qualificationPacket)),
        ]);
    }

    private function lastSuccessfulOutput(array $record, string $transition): array
    {
        for ($index = count($record['events']) - 1; $index >= 0; --$index) {
            $event = $record['events'][$index];
            if (($event['transition'] ?? null) === $transition && ($event['result'] ?? null) === 'SUCCESS') {
                return is_array($event['output'] ?? null) ? $event['output'] : [];
            }
        }

        throw new \RuntimeException(sprintf('Missing successful %s receipt.', $transition));
    }

    private function initialRecord(ValidationReceipt $receipt, string $instanceId): array
    {
        return [
            'schema' => 'imperium.bootstrap-state/v1',
            'state' => BootstrapState::Uninitialized->value,
            'generation' => 0,
            'binding' => [
                'instance_id' => $instanceId,
                'manifest_id' => $receipt->manifestId,
                'charter_generation' => $receipt->charterGeneration,
                'artifact_set_digest' => $receipt->artifactSetDigest,
                'launcher_digest' => $receipt->launcherDigest,
                'mastermason_digest' => $receipt->masterMasonDigest,
            ],
            'events' => [],
        ];
    }

    private function transition(array $record, string $transition, BootstrapState $to, array $output): array
    {
        ++$record['generation'];
        $record['state'] = $to->value;
        $record['events'][] = [
            'transition' => $transition,
            'result' => 'SUCCESS',
            'state' => $to->value,
            'generation' => $record['generation'],
            'occurred_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'transaction_id' => hash('sha256', $record['binding']['instance_id'].'|'.$record['binding']['manifest_id']),
            'output' => $output,
        ];
        $this->store->write($record);
        return $record;
    }

    private function assertSameBinding(array $record, ValidationReceipt $receipt, string $instanceId): void
    {
        $expected = [$instanceId, $receipt->manifestId, $receipt->charterGeneration, $receipt->artifactSetDigest];
        $observed = [
            $record['binding']['instance_id'] ?? null,
            $record['binding']['manifest_id'] ?? null,
            $record['binding']['charter_generation'] ?? null,
            $record['binding']['artifact_set_digest'] ?? null,
        ];
        if ($expected !== $observed) {
            throw new \RuntimeException('B02_INSTANCE_EXISTS: persisted instance binding conflicts with this activation.');
        }
    }
}
