<template>
  <div class="min-h-screen bg-gray-50 p-6 font-sans">
    <!-- Header -->
    <header class="sticky top-0 z-10 bg-white shadow p-4 mb-6 rounded-xl flex items-center justify-between">
      <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Marketplace</h1>
      <input
        v-model="search"
        type="text"
        placeholder="Rechercher un article..."
        class="px-4 py-2 rounded-xl border border-gray-300 bg-gray-100 shadow-sm focus:ring focus:ring-blue-300 focus:bg-white transition"
      />
    </header>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
      <select v-model="selectedCategory" class="filter-select">
        <option value="">Catégorie</option>
        <option v-for="c in categories" :key="c">{{ c }}</option>
      </select>
      <select v-model="selectedCity" class="filter-select">
        <option value="">Ville</option>
        <option v-for="c in cities" :key="c">{{ c }}</option>
      </select>
      <select v-model="selectedRegion" class="filter-select">
        <option value="">Région</option>
        <option v-for="r in regions" :key="r">{{ r }}</option>
      </select>
    </div>

    <!-- Items grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
      <div
        v-for="item in filteredItems"
        :key="item.id"
        class="bg-white rounded-xl shadow hover:shadow-xl transition transform hover:-translate-y-1 p-4 flex flex-col border border-gray-100"
      >
        <img :src="item.image" class="rounded-lg h-40 w-full object-cover mb-3" />
        <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
        <p class="text-sm text-gray-600 flex-1 mt-1">{{ item.description }}</p>
        <p class="text-xl font-bold text-blue-600 mt-3">{{ item.price }} FCFA</p>
        <button
          class="mt-4 bg-blue-600 shadow text-white py-2 rounded-lg hover:bg-blue-700 transition"
        >
          Prendre l'article
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      search: "",
      selectedCategory: "",
      selectedCity: "",
      selectedRegion: "",
      categories: ["Électronique", "Mode", "Maison", "Services"],
      cities: ["Douala", "Yaoundé", "Bafoussam", "Garoua"],
      regions: ["Littoral", "Centre", "Ouest", "Nord"],
      items: [
        {
          id: 1,
          title: "Téléphone Samsung",
          description: "Très bon état, batterie solide.",
          price: 90000,
          image: "https://images.pexels.com/photos/607812/pexels-photo-607812.jpeg",
          category: "Électronique",
          city: "Douala",
          region: "Littoral"
        },
        {
          id: 2,
          title: "Chaussures Nike",
          description: "Pointure 42, neuves.",
          price: 30000,
          image: "https://images.pexels.com/photos/2529148/pexels-photo-2529148.jpeg",
          category: "Mode",
          city: "Yaoundé",
          region: "Centre"
        }
      ]
    }
  },
  computed: {
    filteredItems() {
      return this.items.filter(item => {
        const matchesSearch = item.title.toLowerCase().includes(this.search.toLowerCase())
        const matchesCategory = this.selectedCategory ? item.category === this.selectedCategory : true
        const matchesCity = this.selectedCity ? item.city === this.selectedCity : true
        const matchesRegion = this.selectedRegion ? item.region === this.selectedRegion : true
        return matchesSearch && matchesCategory && matchesCity && matchesRegion
      })
    }
  }
}
</script>

<style scoped>
.filter-select {
  @apply px-4 py-2 rounded-xl border border-gray-300 bg-white shadow-sm focus:ring focus:ring-blue-300 focus:bg-white transition;
}
</style>
