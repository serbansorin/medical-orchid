<?php

namespace App\Enums;

enum MedicalLetterType: string
{
    case Referral = 'referral';
    case SickLeave = 'sick_leave';
    case MedicalCertificate = 'medical_certificate';
    case Consultation = 'consultation';
    case Discharge = 'discharge';
    case MedicalReport = 'medical_report';
    case MedicalOpinion = 'medical_opinion';
    case Recommendation = 'recommendation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Referral => 'Scrisoare de Trimitere',
            self::SickLeave => 'Certificat de Concediu Medical',
            self::MedicalCertificate => 'Adeverință Medicală',
            self::Consultation => 'Scrisoare de Consultație',
            self::Discharge => 'Bilet de Externare',
            self::MedicalReport => 'Raport Medical',
            self::MedicalOpinion => 'Aviz Medical',
            self::Recommendation => 'Recomandare Medicală',
            self::Other => 'Altele',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Referral => 'info',
            self::SickLeave => 'warning',
            self::MedicalCertificate => 'success',
            self::Consultation => 'primary',
            self::Discharge => 'danger',
            self::MedicalReport => 'gray',
            self::MedicalOpinion => 'info',
            self::Recommendation => 'success',
            self::Other => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Referral => 'heroicon-o-arrow-right-circle',
            self::SickLeave => 'heroicon-o-calendar-days',
            self::MedicalCertificate => 'heroicon-o-document-check',
            self::Consultation => 'heroicon-o-clipboard-document-list',
            self::Discharge => 'heroicon-o-arrow-right-on-rectangle',
            self::MedicalReport => 'heroicon-o-document-text',
            self::MedicalOpinion => 'heroicon-o-clipboard-document-check',
            self::Recommendation => 'heroicon-o-light-bulb',
            self::Other => 'heroicon-o-document',
        };
    }

    /**
     * Returns the default field schema for this letter type.
     * Used when seeding global templates.
     */
    public function defaultSchema(): array
    {
        $schema = match ($this) {
            self::Referral => [
                'document_key' => 'scrisoare-trimitere',
                'defaults' => [
                    'title' => 'Scrisoare de trimitere',
                    'content' => 'Se recomandă evaluare în specialitatea indicată, cu scrisoarea medicală și documentele atașate.',
                    'recommendations' => 'Prezentare cu analizele disponibile și documentele medicale anterioare.',
                ],
                'sections' => [
                    ['key' => 'clinical_context', 'title' => 'Context clinic', 'fields' => ['diagnosis', 'clinical_summary', 'investigations_performed', 'treatment_so_far']],
                    ['key' => 'referral_details', 'title' => 'Detalii trimitere', 'fields' => ['reason_for_referral', 'urgency']],
                ],
                'fields' => [
                    ['key' => 'diagnosis', 'type' => 'textarea', 'label' => 'Diagnostic', 'required' => true, 'rows' => 3],
                    ['key' => 'clinical_summary', 'type' => 'textarea', 'label' => 'Rezumat clinic', 'rows' => 4],
                    ['key' => 'investigations_performed', 'type' => 'textarea', 'label' => 'Investigații efectuate', 'rows' => 3],
                    ['key' => 'treatment_so_far', 'type' => 'textarea', 'label' => 'Tratament urmat', 'rows' => 3],
                    ['key' => 'reason_for_referral', 'type' => 'textarea', 'label' => 'Motivul trimiterii', 'required' => true, 'rows' => 3],
                    ['key' => 'urgency', 'type' => 'select', 'label' => 'Urgență', 'options' => ['urgent' => 'Urgent', 'normal' => 'Normal', 'routine' => 'Programat'], 'default' => 'normal'],
                ],
            ],
            self::SickLeave => [
                'document_key' => 'certificat-concediu-medical',
                'defaults' => [
                    'title' => 'Certificat de concediu medical',
                    'recommendations' => 'Repaus la domiciliu și reevaluare la data stabilită.',
                ],
                'sections' => [
                    ['key' => 'medical_basis', 'title' => 'Bază medicală', 'fields' => ['diagnosis', 'icd_code', 'work_capacity']],
                    ['key' => 'leave_period', 'title' => 'Perioada concediului', 'fields' => ['period_start', 'period_end', 'days_off']],
                ],
                'fields' => [
                    ['key' => 'diagnosis', 'type' => 'textarea', 'label' => 'Diagnostic', 'required' => true, 'rows' => 3],
                    ['key' => 'icd_code', 'type' => 'text', 'label' => 'Cod ICD-10'],
                    ['key' => 'work_capacity', 'type' => 'select', 'label' => 'Capacitate de muncă', 'options' => ['reduced' => 'Redusă', 'none' => 'Inexistentă', 'partial' => 'Parțială'], 'default' => 'none'],
                    ['key' => 'period_start', 'type' => 'date', 'label' => 'Data început concediu', 'required' => true],
                    ['key' => 'period_end', 'type' => 'date', 'label' => 'Data sfârșit concediu', 'required' => true],
                    ['key' => 'days_off', 'type' => 'number', 'label' => 'Număr zile concediu', 'required' => true],
                ],
            ],
            self::MedicalCertificate => [
                'document_key' => 'adeverinta-medicala',
                'defaults' => [
                    'title' => 'Adeverință medicală',
                    'content' => 'Prezenta adeverință se eliberează la cerere, pentru a servi la destinația menționată.',
                ],
                'sections' => [
                    ['key' => 'certificate_scope', 'title' => 'Scop și concluzie', 'fields' => ['purpose', 'conclusion', 'valid_from', 'valid_until']],
                    ['key' => 'clinical_details', 'title' => 'Date clinice', 'fields' => ['diagnosis', 'clinical_exam']],
                ],
                'fields' => [
                    ['key' => 'purpose', 'type' => 'text', 'label' => 'Scop / Destinație', 'required' => true],
                    ['key' => 'conclusion', 'type' => 'textarea', 'label' => 'Concluzie', 'required' => true, 'rows' => 3],
                    ['key' => 'valid_from', 'type' => 'date', 'label' => 'Valabil de la'],
                    ['key' => 'valid_until', 'type' => 'date', 'label' => 'Valabil până la'],
                    ['key' => 'diagnosis', 'type' => 'textarea', 'label' => 'Diagnostic', 'rows' => 3],
                    ['key' => 'clinical_exam', 'type' => 'textarea', 'label' => 'Examen clinic', 'rows' => 3],
                ],
            ],
            self::Consultation => [
                'document_key' => 'scrisoare-consultatie',
                'defaults' => [
                    'title' => 'Scrisoare medicală de consultație',
                    'content' => 'Pacient evaluat clinic în cadrul consultației curente.',
                ],
                'sections' => [
                    ['key' => 'visit_context', 'title' => 'Prezentare și anamneză', 'fields' => ['reason_for_visit', 'anamnesis']],
                    ['key' => 'clinical_assessment', 'title' => 'Evaluare clinică', 'fields' => ['clinical_exam', 'diagnosis', 'icd_code', 'paraclinical']],
                    ['key' => 'management_plan', 'title' => 'Plan terapeutic', 'fields' => ['treatment', 'recommendations']],
                ],
                'fields' => [
                    ['key' => 'reason_for_visit', 'type' => 'textarea', 'label' => 'Motiv prezentare', 'required' => true, 'rows' => 3],
                    ['key' => 'anamnesis', 'type' => 'richtext', 'label' => 'Anamneză'],
                    ['key' => 'clinical_exam', 'type' => 'richtext', 'label' => 'Examen clinic'],
                    ['key' => 'diagnosis', 'type' => 'textarea', 'label' => 'Diagnostic', 'required' => true, 'rows' => 3],
                    ['key' => 'icd_code', 'type' => 'text', 'label' => 'Cod ICD-10'],
                    ['key' => 'paraclinical', 'type' => 'textarea', 'label' => 'Investigații paraclinice', 'rows' => 3],
                    ['key' => 'treatment', 'type' => 'richtext', 'label' => 'Tratament'],
                    ['key' => 'recommendations', 'type' => 'textarea', 'label' => 'Recomandări', 'rows' => 3],
                ],
            ],
            self::Discharge => [
                'document_key' => 'bilet-externare',
                'defaults' => [
                    'title' => 'Bilet de externare',
                    'content' => 'Se recomandă respectarea tratamentului și prezentarea la control conform recomandărilor de mai jos.',
                ],
                'sections' => [
                    ['key' => 'hospitalization', 'title' => 'Internare și externare', 'fields' => ['admission_date', 'discharge_date', 'admission_diagnosis', 'discharge_diagnosis']],
                    ['key' => 'clinical_course', 'title' => 'Evoluție clinică', 'fields' => ['anamnesis', 'clinical_exam', 'investigations', 'procedures_performed', 'treatment_during_stay']],
                    ['key' => 'discharge_plan', 'title' => 'Plan la externare', 'fields' => ['discharge_treatment', 'recommendations', 'follow_up']],
                ],
                'fields' => [
                    ['key' => 'admission_date', 'type' => 'date', 'label' => 'Data internării', 'required' => true],
                    ['key' => 'discharge_date', 'type' => 'date', 'label' => 'Data externării', 'required' => true],
                    ['key' => 'admission_diagnosis', 'type' => 'textarea', 'label' => 'Diagnostic la internare', 'required' => true, 'rows' => 3],
                    ['key' => 'discharge_diagnosis', 'type' => 'textarea', 'label' => 'Diagnostic la externare', 'required' => true, 'rows' => 3],
                    ['key' => 'anamnesis', 'type' => 'richtext', 'label' => 'Anamneză'],
                    ['key' => 'clinical_exam', 'type' => 'richtext', 'label' => 'Examen clinic'],
                    ['key' => 'investigations', 'type' => 'richtext', 'label' => 'Investigații'],
                    ['key' => 'procedures_performed', 'type' => 'richtext', 'label' => 'Proceduri efectuate'],
                    ['key' => 'treatment_during_stay', 'type' => 'richtext', 'label' => 'Tratament în timpul internării'],
                    ['key' => 'discharge_treatment', 'type' => 'richtext', 'label' => 'Tratament la externare'],
                    ['key' => 'recommendations', 'type' => 'textarea', 'label' => 'Recomandări', 'rows' => 4],
                    ['key' => 'follow_up', 'type' => 'textarea', 'label' => 'Control / Urmărire', 'rows' => 3],
                ],
            ],
            self::MedicalReport => [
                'document_key' => 'raport-medical',
                'defaults' => [
                    'title' => 'Raport medical',
                    'content' => 'Raport întocmit pe baza datelor clinice și paraclinice disponibile la momentul evaluării.',
                ],
                'sections' => [
                    ['key' => 'assessment', 'title' => 'Evaluare', 'fields' => ['diagnosis', 'anamnesis', 'clinical_exam', 'paraclinical']],
                    ['key' => 'conclusion', 'title' => 'Concluzii și recomandări', 'fields' => ['conclusion', 'recommendations']],
                ],
                'fields' => [
                    ['key' => 'diagnosis', 'type' => 'textarea', 'label' => 'Diagnostic', 'required' => true, 'rows' => 3],
                    ['key' => 'anamnesis', 'type' => 'richtext', 'label' => 'Anamneză'],
                    ['key' => 'clinical_exam', 'type' => 'richtext', 'label' => 'Examen clinic'],
                    ['key' => 'paraclinical', 'type' => 'textarea', 'label' => 'Investigații paraclinice', 'rows' => 3],
                    ['key' => 'conclusion', 'type' => 'textarea', 'label' => 'Concluzie', 'required' => true, 'rows' => 3],
                    ['key' => 'recommendations', 'type' => 'textarea', 'label' => 'Recomandări', 'rows' => 3],
                ],
            ],
            self::MedicalOpinion => [
                'document_key' => 'aviz-medical',
                'defaults' => [
                    'title' => 'Aviz medical',
                    'content' => 'Aviz medical emis în baza evaluării clinice și a documentelor disponibile.',
                ],
                'sections' => [
                    ['key' => 'opinion_scope', 'title' => 'Scop și verdict', 'fields' => ['purpose', 'fitness_status', 'conclusion']],
                    ['key' => 'supporting_data', 'title' => 'Date suport', 'fields' => ['diagnosis', 'clinical_exam', 'paraclinical']],
                ],
                'fields' => [
                    ['key' => 'purpose', 'type' => 'text', 'label' => 'Scop aviz', 'required' => true],
                    ['key' => 'fitness_status', 'type' => 'select', 'label' => 'Stare de aptitudine', 'required' => true, 'options' => ['apt' => 'Apt', 'inapt' => 'Inapt', 'apt_conditional' => 'Apt condiționat', 'temporary_inapt' => 'Inapt temporar'], 'default' => 'apt'],
                    ['key' => 'conclusion', 'type' => 'textarea', 'label' => 'Concluzie', 'required' => true, 'rows' => 3],
                    ['key' => 'diagnosis', 'type' => 'textarea', 'label' => 'Diagnostic', 'rows' => 3],
                    ['key' => 'clinical_exam', 'type' => 'textarea', 'label' => 'Examen clinic', 'rows' => 3],
                    ['key' => 'paraclinical', 'type' => 'textarea', 'label' => 'Investigații paraclinice', 'rows' => 3],
                ],
            ],
            self::Recommendation => [
                'document_key' => 'recomandare-medicala',
                'defaults' => [
                    'title' => 'Recomandare medicală',
                    'content' => 'Se recomandă următorul plan de îngrijire și monitorizare.',
                ],
                'sections' => [
                    ['key' => 'recommendation_context', 'title' => 'Context', 'fields' => ['diagnosis', 'duration', 'follow_up_date']],
                    ['key' => 'recommendation_body', 'title' => 'Recomandare', 'fields' => ['recommendation_details']],
                ],
                'fields' => [
                    ['key' => 'diagnosis', 'type' => 'textarea', 'label' => 'Diagnostic', 'rows' => 3],
                    ['key' => 'duration', 'type' => 'text', 'label' => 'Durată'],
                    ['key' => 'follow_up_date', 'type' => 'date', 'label' => 'Data control'],
                    ['key' => 'recommendation_details', 'type' => 'richtext', 'label' => 'Detalii recomandare', 'required' => true],
                ],
            ],
            self::Other => [
                'document_key' => 'scrisoare-medicala-generala',
                'defaults' => [
                    'title' => 'Scrisoare medicală',
                    'content' => 'Document medical redactat pentru pacient, conform datelor clinice disponibile.',
                ],
                'sections' => [
                    ['key' => 'document_body', 'title' => 'Conținut document', 'fields' => ['content']],
                ],
                'fields' => [
                    ['key' => 'content', 'type' => 'richtext', 'label' => 'Conținut', 'required' => true],
                ],
            ],
        };

        return [
            'version' => 2,
            'country' => 'RO',
            ...$schema,
        ];
    }

    /**
     * Get all types as options array for selects.
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
