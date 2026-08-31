-- Group pediatric postnatal history under one general alert.

ALTER TABLE llx_cabinetmed_patient ADD COLUMN note_alimentation text AFTER note_postnataux;
ALTER TABLE llx_cabinetmed_patient ADD COLUMN statut_vaccination_pev varchar(20) AFTER note_alimentation;
ALTER TABLE llx_cabinetmed_patient ADD COLUMN note_vaccination_pev text AFTER statut_vaccination_pev;
ALTER TABLE llx_cabinetmed_patient ADD COLUMN statut_vaccination_rappels varchar(20) AFTER note_vaccination_pev;
ALTER TABLE llx_cabinetmed_patient ADD COLUMN note_vaccination_rappels text AFTER statut_vaccination_rappels;
ALTER TABLE llx_cabinetmed_patient ADD COLUMN note_scolarite text AFTER note_vaccination_rappels;
