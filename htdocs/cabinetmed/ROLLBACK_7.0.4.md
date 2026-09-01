# Rollback du bloc Drépanocytose (7.0.4)

Le rollback recommandé retire uniquement le code et conserve la table
`llx_cabinetmed_patient_drepano`. Les données saisies restent donc récupérables
si le bloc est réactivé plus tard.

## 1. Sauvegarder la table

```bash
mysqldump --single-transaction \
  -u root -p dolimed_globale llx_cabinetmed_patient_drepano \
  > /tmp/llx_cabinetmed_patient_drepano-$(date +%F-%H%M).sql
```

## 2. Revenir au code précédent

Sur le dépôt de développement, identifier le commit :

```bash
git log --grep="bloc Drépanocytose" --format='%H' -1
```

Puis créer un commit inverse et le publier :

```bash
git revert COMMIT_A_REVERTIR
git push origin feature/atcd-traitements
```

Sur le VPS :

```bash
git checkout feature/atcd-traitements
git pull --ff-only origin feature/atcd-traitements
```

La table peut rester en base : elle n'a aucun effet avec l'ancien code.

## 3. Suppression définitive facultative

Seulement après validation de la sauvegarde :

```bash
mysql -u root -p dolimed_globale \
  < htdocs/cabinetmed/rollback/7.0.4-drop-drepano.sql
```

Cette dernière opération supprime définitivement les données du bloc.
