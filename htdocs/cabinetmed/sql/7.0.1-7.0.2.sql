-- Add pediatric history fields to the patient record.

ALTER TABLE llx_cabinetmed_patient ADD COLUMN note_antenataux text AFTER note_traitspec;
ALTER TABLE llx_cabinetmed_patient ADD COLUMN note_perinataux text AFTER note_antenataux;
ALTER TABLE llx_cabinetmed_patient ADD COLUMN note_postnataux text AFTER note_perinataux;

ALTER TABLE llx_cabinetmed_patient ADD COLUMN alert_antenataux smallint AFTER alert_traitspec;
ALTER TABLE llx_cabinetmed_patient ADD COLUMN alert_perinataux smallint AFTER alert_antenataux;
ALTER TABLE llx_cabinetmed_patient ADD COLUMN alert_postnataux smallint AFTER alert_perinataux;
