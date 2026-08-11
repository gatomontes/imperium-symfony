<?php

declare(strict_types=1);

namespace App\Bootstrap;

final readonly class MasterMason
{
    public function __construct(private StateStore $store)
    {
    }

    public function executeThroughReadiness(ValidationReceipt $receipt, string $instanceId): array
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
                $record = $this->assembleCurianCore($record, $receipt, $instanceId);
            }
            if (BootstrapState::CurianCoreAssembled === BootstrapState::from($record['state'])) {
                $record = $this->activateCuria($record, $receipt, $instanceId);
            }
            if (BootstrapState::CuriaActive === BootstrapState::from($record['state'])) {
                $record = $this->bindCurianCore($record, $instanceId);
            }
            if (BootstrapState::CurianCoreBoundInactive === BootstrapState::from($record['state'])) {
                $record = $this->attachCurialSecretary($record, $receipt, $instanceId);
            }
            if (BootstrapState::SecretaryBoundInactive === BootstrapState::from($record['state'])) {
                $record = $this->verifyPrimordialRoutes($record, $receipt, $instanceId);
            }
            if (BootstrapState::RoutesVerified === BootstrapState::from($record['state'])) {
                $record = $this->establishReadiness($record, $instanceId);
            }

            return $record;
        });
    }

    private function establishReadiness(array $record, string $instanceId): array
    {
        $t08 = $this->lastSuccessfulOutput($record, 'T08');
        $t09 = $this->lastSuccessfulOutput($record, 'T09');
        $runtime = $t08['runtime'] ?? null;
        $configuration = $t09['route_configuration'] ?? null;
        $expectedRoutes = $this->curianRoutes(true);
        if (!is_array($runtime) || !is_array($configuration)
            || 'verified-disabled' !== ($configuration['status'] ?? null)
            || $this->curianRoutes(false) !== ($configuration['routes'] ?? null)
            || true !== ($t09['all_probes_passed'] ?? null)
            || false !== ($t09['work_delivered'] ?? null)
            || $instanceId.'.office.curia' !== ($runtime['runtime_id'] ?? null)
            || false !== ($runtime['addressable'] ?? null)
        ) {
            throw new \RuntimeException('B80_ROUTE_PROOF_INVALID: Curia readiness proof is incomplete.');
        }
        $readyRuntime = $runtime;
        foreach (['seneschal', 'chamberlain', 'secretary'] as $role) {
            if ('bound-inactive' !== ($readyRuntime['occupants'][$role]['status'] ?? null)) {
                throw new \RuntimeException('B81_ACTIVATION_PRECONDITION_FAILED: '.$role.' is not bound inactive.');
            }
            $readyRuntime['occupants'][$role]['status'] = 'active';
        }
        $readyRuntime['mode'] = 'operator-facing';
        $readyRuntime['addressable'] = true;
        $enabled = $configuration;
        $enabled['status'] = 'enabled';
        $enabled['routes'] = $expectedRoutes;

        return $this->transition($record, 'T10', BootstrapState::CuriaReady, [
            'route_configuration' => $enabled,
            'route_configuration_digest' => hash('sha256', CanonicalJson::encode($enabled)),
            'runtime' => $readyRuntime,
            'routes_enabled' => true,
            'officers_active' => true,
            'operator_entrypoint' => 'curia.imperator',
            'curia_addressable' => true,
            'secretary_optional' => true,
            'activation_atomic' => true,
        ]);
    }

    private function verifyPrimordialRoutes(array $record, ValidationReceipt $receipt, string $instanceId): array
    {
        $t08 = $this->lastSuccessfulOutput($record, 'T08');
        $runtime = $t08['runtime'] ?? null;
        $bindings = $t08['bindings'] ?? null;
        if (!is_array($runtime) || !is_array($bindings) || $instanceId.'.office.curia' !== ($runtime['runtime_id'] ?? null)) {
            throw new \RuntimeException('B71_ENDPOINT_MISMATCH: Curia bindings are absent.');
        }
        $resolved = [];
        foreach (['seneschal', 'chamberlain', 'secretary'] as $role) {
            $endpoint = 'curia.'.$role;
            $occupant = $runtime['occupants'][$role] ?? null;
            if (!is_array($occupant) || $endpoint !== ($occupant['seat'] ?? null) || 'bound-inactive' !== ($occupant['status'] ?? null)) {
                throw new \RuntimeException('B71_ENDPOINT_MISMATCH: '.$endpoint.' is not bound inactive.');
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
        $expectedRoutes = array_map(static fn (array $route): array => ['from' => $route['from'], 'to' => $route['to']], $this->curianRoutes(false));
        $expectedProbeChecks = ['exact-endpoint-resolution', 'bound-occupancy-generation', 'shared-curia-runtime', 'no-office-work-delivery'];
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
                    'shared-curia-runtime' => $source['runtime_id'] === $target['runtime_id'],
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

        return $this->transition($record, 'T09', BootstrapState::RoutesVerified, [
            'route_configuration' => $configuration,
            'route_configuration_digest' => hash('sha256', CanonicalJson::encode($configuration)),
            'endpoint_bindings' => $resolved,
            'probes' => $probes,
            'all_probes_passed' => true,
            'work_delivered' => false,
            'routes_enabled' => false,
            'officers_active' => false,
            'curia_addressable' => false,
            'runtime' => $runtime,
        ]);
    }

    private function curianRoutes(bool $enabled): array
    {
        return array_map(
            static fn (array $route): array => $route + ['enabled' => $enabled],
            [
                ['from' => 'curia.secretary', 'to' => 'curia.seneschal'],
                ['from' => 'curia.seneschal', 'to' => 'curia.secretary'],
                ['from' => 'curia.chamberlain', 'to' => 'curia.seneschal'],
                ['from' => 'curia.seneschal', 'to' => 'curia.chamberlain'],
            ],
        );
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

    private function bindCurianCore(array $record, string $instanceId): array
    {
        $t05 = $this->lastSuccessfulOutput($record, 'T05');
        $t06 = $this->lastSuccessfulOutput($record, 'T06');
        $packets = $t05['delivery_packets'] ?? null;
        $reservations = $t05['seat_reservations'] ?? null;
        $runtime = $t06['runtime'] ?? null;
        if (!is_array($packets)
            || !is_array($reservations)
            || !is_array($runtime)
            || true !== ($t05['atomic_pair'] ?? null)
            || true !== ($t06['activation_atomic'] ?? null)
        ) {
            throw new \RuntimeException('B60_BINDING_INPUT_MISMATCH: atomic T05/T06 inputs are absent.');
        }

        $specifications = [
            'seneschal' => 'curia.seneschal',
            'chamberlain' => 'curia.chamberlain',
        ];
        $bindings = [];
        $boundRuntime = $runtime;
        foreach ($specifications as $role => $seat) {
            $packet = $packets[$role] ?? null;
            $reservation = $reservations[$role] ?? null;
            if (!is_array($packet)
                || !is_array($reservation)
                || true !== ($packet['sealed'] ?? null)
                || 'qualified-unbound' !== ($packet['candidate']['status'] ?? null)
                || $seat !== ($packet['candidate']['target_seat'] ?? null)
                || 1 !== ($packet['candidate']['target_occupancy_generation'] ?? null)
                || $seat !== ($reservation['seat'] ?? null)
                || 0 !== ($reservation['expected_generation'] ?? null)
                || 'HELD' !== ($reservation['status'] ?? null)
                || $instanceId.'.office.curia' !== ($runtime['runtime_id'] ?? null)
                || 'inactive-unavailable' !== ($runtime['mode'] ?? null)
                || false !== ($runtime['addressable'] ?? null)
                || null !== ($runtime['occupants'][$role] ?? null)
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
                'seat' => $seat,
                'occupancy_generation' => 1,
                'status' => 'bound-inactive',
                'source_packet_digest' => $packetDigest,
            ];
            $boundRuntime['occupants'][$role] = $occupant;
            $bindings[$role] = [
                'seat' => $seat,
                'prior_occupancy_generation' => 0,
                'occupancy_generation' => 1,
                'occupant' => $occupant,
                'packet_disposition' => 'consumed-by-binding',
            ];
        }

        return $this->transition($record, 'T07', BootstrapState::CurianCoreBoundInactive, [
            'bindings' => $bindings,
            'runtime' => $boundRuntime,
            'binding_atomic' => true,
            'officers_active' => false,
            'curia_addressable' => false,
        ]);
    }

    private function attachCurialSecretary(array $record, ValidationReceipt $receipt, string $instanceId): array
    {
        $t07 = $this->lastSuccessfulOutput($record, 'T07');
        $runtime = $t07['runtime'] ?? null;
        $commission = $receipt->secretaryCommission;
        $commissionRecord = $receipt->manifest['unsigned_payload']['primordial']['assembly_commissions']['secretary'] ?? null;
        $expected = [
            'primordial.curial-secretary-assembly.1', true, 'charter-development-1',
            'conscription.recruiter', 'T07.curian-core-bound', 2,
            'curia.secretary', 'curia.secretary@1.0.0',
            'generic-officer.curial-secretary@1.0.0', 'qualification.curia.secretary.v1',
            0, true, true,
        ];
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
            $commission['constraints']['independent_from_governing_pair'] ?? null,
            $commission['constraints']['curia_runtime_required'] ?? null,
        ];
        if (!is_array($runtime)
            || $instanceId.'.office.curia' !== ($runtime['runtime_id'] ?? null)
            || null !== ($runtime['occupants']['secretary'] ?? null)
            || $expected !== $observed
            || !is_array($commissionRecord)
            || !isset($commissionRecord['digest'])
        ) {
            throw new \RuntimeException('B65_SECRETARY_ATTACHMENT_FAILED: Curial Secretary inputs are invalid.');
        }

        $candidateId = $instanceId.'.officer.isolde.1';
        $qualification = [
            'commission_id' => $commission['id'],
            'commission_digest' => $commissionRecord['digest'],
            'candidate_id' => $candidateId,
            'persona' => 'isolde',
            'profile' => 'curia.secretary@1.0.0',
            'substrate' => 'generic-officer.curial-secretary@1.0.0',
            'qualification_contract' => 'qualification.curia.secretary.v1',
            'limitation' => 'provisional-curial-assignment',
        ];
        $occupant = [
            'manifestation_id' => $candidateId,
            'seat' => 'curia.secretary',
            'occupancy_generation' => 1,
            'status' => 'bound-inactive',
            'qualification_digest' => hash('sha256', CanonicalJson::encode($qualification)),
        ];
        $runtime['occupants']['secretary'] = $occupant;
        $bindings = $t07['bindings'];
        $bindings['secretary'] = [
            'seat' => 'curia.secretary',
            'prior_occupancy_generation' => 0,
            'occupancy_generation' => 1,
            'occupant' => $occupant,
            'commission_disposition' => 'consumed-by-binding',
        ];

        return $this->transition($record, 'T08', BootstrapState::SecretaryBoundInactive, [
            'runtime' => $runtime,
            'bindings' => $bindings,
            'qualification_packet' => $qualification,
            'secretary_optional' => true,
            'secretary_provisional' => true,
            'curia_addressable' => false,
        ]);
    }

    private function activateCuria(array $record, ValidationReceipt $receipt, string $instanceId): array
    {
        $t05 = $this->lastSuccessfulOutput($record, 'T05');
        $packets = $t05['delivery_packets'] ?? null;
        $reservations = $t05['seat_reservations'] ?? null;
        if (!is_array($packets) || !is_array($reservations) || true !== ($t05['atomic_pair'] ?? null)) {
            throw new \RuntimeException('B51_PACKET_STALE: T05 did not produce an atomic delivery pair.');
        }

        $specifications = [
            'seneschal' => 'curia.seneschal',
            'chamberlain' => 'curia.chamberlain',
        ];
        foreach ($specifications as $role => $seat) {
            $packet = $packets[$role] ?? null;
            $reservation = $reservations[$role] ?? null;
            if (!is_array($packet)
                || !is_array($reservation)
                || true !== ($packet['sealed'] ?? null)
                || 'qualified-unbound' !== ($packet['candidate']['status'] ?? null)
                || $seat !== ($packet['candidate']['target_seat'] ?? null)
                || 1 !== ($packet['candidate']['target_occupancy_generation'] ?? null)
                || $seat !== ($reservation['seat'] ?? null)
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

        $definition = $manifestOffices['curia'] ?? null;
        if (!is_array($definition) || !isset($definition['artifact'], $definition['version'], $definition['digest'])) {
            throw new \RuntimeException('B50_OFFICE_MISMATCH: pinned Curia definition is invalid.');
        }
        $runtimeId = $instanceId.'.office.curia';
        if ($this->outputContainsRuntime($record, $runtimeId)) {
            throw new \RuntimeException('B52_OFFICE_EXISTS: Curia runtime already exists.');
        }
        $runtime = [
            'runtime_id' => $runtimeId,
            'definition' => $definition,
            'charter_generation' => $receipt->charterGeneration,
            'mode' => 'inactive-unavailable',
            'addressable' => false,
            'occupants' => ['seneschal' => null, 'chamberlain' => null, 'secretary' => null],
        ];

        return $this->transition($record, 'T06', BootstrapState::CuriaActive, [
            'runtime' => $runtime,
            'activation_atomic' => true,
            'delivery_packets_preserved' => [
                'seneschal' => $packets['seneschal']['packet_digest'],
                'chamberlain' => $packets['chamberlain']['packet_digest'],
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

    private function assembleCurianCore(array $record, ValidationReceipt $receipt, string $instanceId): array
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
            'seneschal' => [
                'commission' => $receipt->seneschalCommission,
                'id' => 'primordial.seneschal-assembly.1',
                'seat' => 'curia.seneschal',
                'profile' => 'curia.seneschal@1.0.0',
                'substrate' => 'generic-officer.seneschal@1.0.0',
                'qualification' => 'qualification.curia.seneschal.v1',
            ],
            'chamberlain' => [
                'commission' => $receipt->chamberlainCommission,
                'id' => 'primordial.chamberlain-assembly.1',
                'seat' => 'curia.chamberlain',
                'profile' => 'curia.chamberlain@1.0.0',
                'substrate' => 'generic-officer.chamberlain@1.0.0',
                'qualification' => 'qualification.curia.chamberlain.v1',
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
                'seneschal' === $role ? 'primordial.chamberlain-assembly.1' : 'primordial.seneschal-assembly.1', true,
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

        return $this->transition($record, 'T05', BootstrapState::CurianCoreAssembled, [
            'recruiter_manifestation_id' => $recruiter['manifestation_id'],
            'assembly_attempt' => 1,
            'seat_reservations' => [
                'seneschal' => ['seat' => 'curia.seneschal', 'expected_generation' => 0, 'status' => 'HELD'],
                'chamberlain' => ['seat' => 'curia.chamberlain', 'expected_generation' => 0, 'status' => 'HELD'],
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
