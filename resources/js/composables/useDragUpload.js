import { ref } from 'vue'

/**
 * Reusable drag-and-drop helper for admin image upload zones.
 *
 *   const { isDragging, dragHandlers } = useDragUpload((files) => handleFiles(files))
 *   <div v-bind="dragHandlers" :class="isDragging ? 'active' : ''">…</div>
 *
 * Uses an enter/leave depth counter so hovering child elements doesn't flicker.
 */
export function useDragUpload(onDropFiles) {
  const isDragging = ref(false)
  let depth = 0

  const onDragEnter = (e) => { e.preventDefault(); depth++; isDragging.value = true }
  const onDragOver  = (e) => { e.preventDefault() }
  const onDragLeave = (e) => {
    e.preventDefault()
    depth = Math.max(0, depth - 1)
    if (depth === 0) isDragging.value = false
  }
  const onDrop = (e) => {
    e.preventDefault()
    depth = 0
    isDragging.value = false
    onDropFiles(Array.from(e.dataTransfer?.files ?? []))
  }

  return {
    isDragging,
    dragHandlers: {
      onDragenter: onDragEnter,
      onDragover:  onDragOver,
      onDragleave: onDragLeave,
      onDrop,
    },
  }
}

/** Shared image validation — returns { valid, errors } */
export function validateImages(fileList, maxSizeMB = 5) {
  const ALLOWED = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml']
  const valid = []
  const errors = []
  for (const file of Array.from(fileList)) {
    if (!ALLOWED.includes(file.type)) {
      errors.push(`"${file.name}" is not a supported image type.`)
      continue
    }
    if (file.size > maxSizeMB * 1024 * 1024) {
      errors.push(`"${file.name}" exceeds the ${maxSizeMB} MB limit.`)
      continue
    }
    valid.push(file)
  }
  return { valid, errors }
}
