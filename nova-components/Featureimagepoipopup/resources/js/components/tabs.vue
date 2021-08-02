<template>
  <div style="width: 100%;">
    <div class="tabs">
      <ul>
        <li v-for="tab in tabs" :class="{ 'is-active': tab.isActive }">
          <a :href="tab.href" @click="selectTab(tab)">{{ tab.name }}</a>
        </li>
        <li style="margin-left: auto;">
          <a href="javascript:;" class="close-button" @click="cancelUpload()"
             style="border-bottom: 0px solid #dbdbdb;">
            <i class="webmapp-icon-close-outline"></i></a>
        </li>
      </ul>
    </div>

    <div class="tabs-details">
      <slot></slot>
    </div>
  </div>
</template>

<script>
export default {
  name: "tabs",


  data() {
    return {tabs: []};
  },

  created() {

    this.tabs = this.$children;

  },
  methods: {
    selectTab(selectedTab) {
      this.tabs.forEach(tab => {
        tab.isActive = (tab.name == selectedTab.name);
      });
    },
    cancelUpload() {
      this.$parent.modalOpen = false;
    },
  }
};
</script>

<style scoped>
a {
  font-style: normal;
  font-weight: normal;
  font-size: 16px;
  line-height: 22px;
  letter-spacing: 0.459184px;
  color: black;
  cursor: pointer;
  text-decoration: none;
  -webkit-transition: none 86ms ease-out;
  transition: none 86ms ease-out;
}

.tabs ul {
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
  border-bottom: 1px solid #dbdbdb;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-flex: 1;
  -ms-flex-positive: 1;
  flex-grow: 1;
  -ms-flex-negative: 0;
  flex-shrink: 0;
  -webkit-box-pack: start;
  -ms-flex-pack: start;
  justify-content: flex-start;
  margin-bottom: 10px;
}

.tabs li {
  display: block;
}

.tabs a {
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
  border-bottom: 1px solid #dbdbdb;
  color: #4a4a4a;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-pack: center;
  -ms-flex-pack: center;
  justify-content: center;
  margin-bottom: -1px;
  padding: .5em 1em;
  vertical-align: top;
}

.tabs li.is-active a {
  border-bottom: 2px solid #63A2DE !important;
  color: black;
  font-weight: bold;
}
</style>