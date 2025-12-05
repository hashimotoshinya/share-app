<template>
  <div class="space-y-0">

    <!-- 投稿が0件の場合 -->
    <p v-if="!posts || posts.length === 0" class="text-center text-gray-400 mt-10">
      投稿がありません
    </p>

    <PostCard
      v-for="post in posts"
      :key="post.id"
      :username="post.user?.name"
      :content="post.content"
      :likes="post.likes_count"
      :liked="post.liked"
      :is-mine="post.is_mine"
      @like="$emit('like', post.id)"
      @delete="$emit('delete', post.id)"
      @comment="$emit('comment', post.id)"
    />
  </div>
</template>

<script>
import PostCard from './PostCard.vue';

export default {
  components: { PostCard },
  props: {
    posts: Array,
  },
};
</script>
