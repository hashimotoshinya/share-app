<template>
  <div class="w-full text-white border-l border-white">

    <div class="border-t border-white"></div>

    <div class="py-3">

      <div class="flex items-center gap-6 mb-1 px-5">
        <p class="font-bold text-base">{{ username }}</p>

                <div class="flex items-center gap-4">
                  <!-- いいね -->
                    <button
                      @click="$emit('like')"
                      class="flex items-center gap-1 hover:opacity-70 transition"
                      aria-label="like"
                      :disabled="isMine"
                    >
                    <!-- ハート画像: liked時は赤フィルタを適用（heart-red.png不要） -->
                    <img
                      src="/heart.png"
                      class="w-5 h-5 transition-all duration-150"
                      :style="liked ? 'filter: brightness(0) saturate(100%) invert(12%) sepia(96%) saturate(3000%) hue-rotate(342deg);' : ''"
                      alt="heart"
                    />
                    <span :class="isMine ? 'text-gray-400' : 'text-white'" class="text-sm">{{ likes }}</span>
                  </button>

          <!-- 削除 -->
          <button @click="$emit('delete')" class="hover:opacity-70 transition">
            <img src="/cross.png" class="w-5 h-5" />
          </button>

          <!-- コメント -->
          <button @click="$emit('comment')" class="hover:opacity-70 transition ml-7">
            <img src="/detail.png" class="w-5 h-5" />
          </button>
        </div>

      </div>

      <!-- 本文 -->
      <p class="text-gray-300 text-sm whitespace-pre-line px-5">
        {{ content }}
      </p>
    </div>

    <div class="border-b border-white"></div>

  </div>
</template>

<script setup>
defineProps({
  username: {
    type: String,
    required: true
  },
  content: {
    type: String,
    required: true
  },
  likes: {
    type: Number,
    default: 0
  }
 ,
  liked: {
    type: Boolean,
    default: false
  }
  ,
  isMine: {
    type: Boolean,
    default: false
  }
});

defineEmits(["like", "comment", "delete"]);
</script>