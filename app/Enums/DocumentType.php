<?php

namespace App\Enums;

enum DocumentType: string
{
    case CertificateOfGrades = 'certificate_of_grades';
    case ProofOfResidence = 'proof_of_residence';
    case BarangayCertificate = 'barangay_cert';

    public function label(): string
    {
        return match ($this) {
            self::CertificateOfGrades => 'Certificate of Grades',
            self::ProofOfResidence => 'Proof of Residence',
            self::BarangayCertificate => 'Barangay Certificate',
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
