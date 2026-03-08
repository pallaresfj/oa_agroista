#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEST_PUBLIC_DIR="$ROOT_DIR/storage/app/public"

IFS=':' read -r -a SOURCE_DIRS <<< "${MEDIA_SYNC_SOURCE_DIRS:-/Users/pallaresfj/Herd/plan/storage/app/public}"

mkdir -p "$DEST_PUBLIC_DIR/plan-cover" "$DEST_PUBLIC_DIR/center-cover"

copied_files=0

copy_missing_from_source() {
  local source_dir="$1"

  if [[ ! -d "$source_dir" ]]; then
    echo "Aviso: fuente no encontrada, se omite: $source_dir"
    return
  fi

  for bucket in plan-cover center-cover; do
    if [[ ! -d "$source_dir/$bucket" ]]; then
      continue
    fi

    while IFS= read -r -d '' source_file; do
      local relative_path="${source_file#"$source_dir/"}"
      local destination_file="$DEST_PUBLIC_DIR/$relative_path"

      mkdir -p "$(dirname "$destination_file")"

      if [[ ! -f "$destination_file" ]]; then
        cp "$source_file" "$destination_file"
        copied_files=$((copied_files + 1))
      fi
    done < <(find "$source_dir/$bucket" -type f -print0)
  done
}

for source_dir in "${SOURCE_DIRS[@]}"; do
  if [[ "$source_dir" == "$DEST_PUBLIC_DIR" ]]; then
    continue
  fi

  copy_missing_from_source "$source_dir"
done

if [[ -L "$ROOT_DIR/public/storage" || -e "$ROOT_DIR/public/storage" ]]; then
  echo "Enlace public/storage ya existe."
else
  (cd "$ROOT_DIR" && php artisan storage:link)
fi

missing_plan_images="$(
  cd "$ROOT_DIR" && php artisan tinker --execute='
    $missing = App\Models\Plan::query()
      ->whereNotNull("cover")
      ->where("cover", "!=", "")
      ->get(["cover"])
      ->filter(function ($plan) {
          return !Illuminate\Support\Facades\Storage::disk("public")->exists((string) $plan->cover);
      })
      ->count();
    echo $missing;
  '
)"

missing_center_images="$(
  cd "$ROOT_DIR" && php artisan tinker --execute='
    $missing = App\Models\Center::query()
      ->whereNotNull("image_path")
      ->where("image_path", "!=", "")
      ->get(["image_path"])
      ->filter(function ($center) {
          return !Illuminate\Support\Facades\Storage::disk("public")->exists((string) $center->image_path);
      })
      ->count();
    echo $missing;
  '
)"

echo "----------------------------------------"
echo "Sync completado."
echo "copied_files=$copied_files"
echo "missing_plan_images=$missing_plan_images"
echo "missing_center_images=$missing_center_images"

if [[ "$missing_plan_images" != "0" || "$missing_center_images" != "0" ]]; then
  echo "WARNING: quedan imágenes faltantes. Se aplicará fallback visual en vistas públicas."
fi
