<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import {
    destroy as destroyBatch,
    edit as editBatch,
    index as batchesIndex,
    show as showBatch,
    update as updateBatch,
} from '@/routes/batches';
import type { BreadcrumbItem, User, InternshipBatch } from '@/types';

defineOptions({
    layout: [],
});

interface Props {
    batch: InternshipBatch;
    coordinators: User[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Batches',
        href: batchesIndex(),
    },
    {
        title: props.batch.batch_code,
        href: showBatch(props.batch.id),
    },
    {
        title: 'Edit',
        href: editBatch(props.batch.id),
    },
];

const form = useForm({
    batch_code: props.batch.batch_code,
    name: props.batch.name,
    description: props.batch.description || '',
    start_date: props.batch.start_date,
    end_date: props.batch.end_date,
    capacity: props.batch.capacity,
    expected_working_days: props.batch.expected_working_days,
    status: props.batch.status,
    coordinator_id: props.batch.coordinator_id,
});

const submit = () => {
    form.put(updateBatch.url(props.batch.id));
};

const deleteBatch = () => {
    if (confirm('Are you sure you want to archive this batch?')) {
        form.delete(destroyBatch.url(props.batch.id));
    }
};
</script>

<template>
    <Head :title="'Edit ' + batch.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link :href="showBatch(batch.id)">
                            <ArrowLeft class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight">Edit Batch</h2>
                        <p class="text-muted-foreground">Modify settings for {{ batch.batch_code }}</p>
                    </div>
                </div>
                <Button variant="destructive" @click="deleteBatch">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Archive Batch
                </Button>
            </div>

            <form @submit.prevent="submit" class="grid gap-6 md:grid-cols-2">
                <Card class="md:col-span-1">
                    <CardHeader>
                        <CardTitle>Basic Information</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="batch_code">Batch Code</Label>
                            <Input
                                id="batch_code"
                                v-model="form.batch_code"
                                :class="{ 'border-destructive': form.errors.batch_code }"
                            />
                            <p v-if="form.errors.batch_code" class="text-xs text-destructive">{{ form.errors.batch_code }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="name">Batch Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="coordinator">Batch Coordinator</Label>
                            <Select v-model="form.coordinator_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select a coordinator" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="user in coordinators" :key="user.id" :value="user.id.toString()">
                                        {{ user.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <Card class="md:col-span-1">
                    <CardHeader>
                        <CardTitle>Settings & Period</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    v-model="form.start_date"
                                    :class="{ 'border-destructive': form.errors.start_date }"
                                />
                                <p v-if="form.errors.start_date" class="text-xs text-destructive">{{ form.errors.start_date }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="end_date">End Date</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    v-model="form.end_date"
                                    :class="{ 'border-destructive': form.errors.end_date }"
                                />
                                <p v-if="form.errors.end_date" class="text-xs text-destructive">{{ form.errors.end_date }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="capacity">Capacity (Slots)</Label>
                                <Input
                                    id="capacity"
                                    type="number"
                                    v-model="form.capacity"
                                    :class="{ 'border-destructive': form.errors.capacity }"
                                />
                                <p v-if="form.errors.capacity" class="text-xs text-destructive">{{ form.errors.capacity }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="working_days">Expected Working Days</Label>
                                <Input
                                    id="working_days"
                                    type="number"
                                    v-model="form.expected_working_days"
                                    :class="{ 'border-destructive': form.errors.expected_working_days }"
                                />
                                <p v-if="form.errors.expected_working_days" class="text-xs text-destructive">{{ form.errors.expected_working_days }}</p>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="closed">Closed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <div class="md:col-span-2 flex justify-end gap-3">
                    <Button variant="outline" as-child :disabled="form.processing">
                        <Link :href="showBatch(batch.id)">Cancel</Link>
                    </Button>
                    <Button :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Update Batch' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
