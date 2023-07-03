Vue.component('selectix', {
    props: {
        items2: Array
    },
    data: function () {
        return {
            items: [],
            selectixWord: '',
            newItem: '',
        }
    },
    computed: {
        selectixList() {
            return this.items.length > 0 || this.selectixWord != '';
        }
    },
    created() {
        let self = this;
        document.addEventListener('click', function () {
            self.selectixWord = '';
            self.items = [];
        });
    },
    methods: {
        selectixQuery() {
            if (this.selectixWord == '') return false;
            let filters = {
                q: this.selectixWord
            };
            axios.get('/api2.php?action=stock_items&filters=' + JSON.stringify(filters))
                .then(response => this.items = response.data);
        },
        selectixSelect(item) {
            this.$emit('input', item.id)
            this.newItem = item.name;
            this.selectixWord = '';
            this.items = [];
        },
        selectixAddItem() {
            let data = new FormData();
            data.append('name', this.selectixWord);
            axios.post('/api2.php?action=stock_items_edit', data)
                .then(response => this.selectixSelect(response.data));
        },
    },
    template: `<div class="selectix">
<input type="text" class="form-control selectix__input"
v-model="selectixWord"
@keyup="selectixQuery"
:placeholder="(newItem) ? newItem : 'ТМЦ'"
>
<div class="selectix__list" v-if="selectixList">
    <div v-for="item in items" @click="selectixSelect(item)" class="selectix__item">
        {{ item.name }}
    </div>
    <div class="selectix__add" v-if="items.length==0 && selectixWord!=''">
        <a href="#" @click.prevent="selectixAddItem">Добавить</a>
    </div>
</div>
</div>`
})
