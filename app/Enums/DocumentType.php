<?php

namespace App\Enums;

enum DocumentType: string
{
    case CertificateOfGrades = 'certificate_of_grades';
    case CertificateOfIndigency = 'certificate_of_indigency';
    case CertificateOfRegistration = 'certificate_of_registration';
    case ProofOfResidence = 'proof_of_residence';
    case BarangayCertificate = 'barangay_cert';
    case EmployeeProofOfKinship = 'employee_id_kinship';

    public function label(): string
    {
        return match ($this) {
            self::CertificateOfGrades => 'Certificate of Grades',
            self::CertificateOfIndigency => 'Certificate of Indigency',
            self::CertificateOfRegistration => 'Certificate of Registration (COR)',
            self::ProofOfResidence => 'Proof of Residence',
            self::BarangayCertificate => 'Barangay Certificate',
            self::EmployeeProofOfKinship => 'Employee ID / Proof of Kinship',
        };
    }

    /**
     * @return list<self>
     */
    public static function requiredForApplication(): array
    {
        return [
            self::CertificateOfGrades,
            self::ProofOfResidence,
            self::BarangayCertificate,
        ];
    }

    /**
     * Map a sponsorship program's required-document checklist label to the
     * upload document type(s) it produces. Proof of Residence and Barangay
     * Certificate are interchangeable (either satisfies the "residence" slot).
     *
     * @return list<self>
     */
    public static function typesForLabel(string $label): array
    {
        $key = strtolower((string) preg_replace('/\s+/', ' ', trim($label)));
        $direct = self::tryFrom($key);

        return match ($key) {
            'report card / certificate of grades' => [self::CertificateOfGrades],
            'certificate of indigency' => [self::CertificateOfIndigency],
            'certificate of registration (cor)' => [self::CertificateOfRegistration],
            'proof of residence / barangay cert' => [self::ProofOfResidence, self::BarangayCertificate],
            'employee id / proof of kinship' => [self::EmployeeProofOfKinship],
            default => $direct !== null ? [$direct] : [],
        };
    }

    /**
     * Canonical type used when comparing required vs. submitted documents:
     * BarangayCertificate and ProofOfResidence resolve to the same requirement.
     */
    public static function canonicalValue(self|string $type): string
    {
        $value = $type instanceof self ? $type->value : (string) $type;

        return $value === self::BarangayCertificate->value
            ? self::ProofOfResidence->value
            : $value;
    }
}
