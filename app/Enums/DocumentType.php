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
}
