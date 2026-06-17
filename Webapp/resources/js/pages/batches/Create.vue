<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
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
    create as createBatch,
    index as batchesIndex,
    store as storeBatch,
} from '@/routes/batches';
import type { BreadcrumbItem, User } from '@/types';

defineOptions({
    layout: [],
});

interface Props {
    coordinators: User[];
}

defineProps<Props>();

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
        title: 'Create Batch',
        href: createBatch(),
    },
];

const form = useForm({
    batch_code: '',
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    capacity: 20,
    expected_working_days: 50,
    status: 'active',
    coordinator_id: null as number | null,
});

const submit = () => {
    form.post(storeBatch.url());
};
</script>

<template>
    <Head title="Create Batch" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" as-child>
                    <Link :href="batchesIndex()">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Create New Batch</h2>
                    <p class="text-muted-foreground">Define a new internship cycle.</p>
                </div>
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
                                placeholder="e.g., INT-2026-SPRING"
                                :class="{ 'border-destructive': form.errors.batch_code }"
                            />
                            <p v-if="form.errors.batch_code" class="text-xs text-destructive">{{ form.errors.batch_code }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="name">Batch Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g., Spring 2026 Interns"
                                :class="{ 'border-destructive': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Description</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                placeholder="Optional description..."
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
                            <Label for="status">Initial Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active (Standard)</SelectItem>
                                    <SelectItem value="closed">Closed</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-[0.8rem] text-muted-foreground">
                                Active batches with future start dates will automatically show as "Upcoming".
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div class="md:col-span-2 flex justify-end gap-3">
                    <Button variant="outline" as-child :disabled="form.processing">
                        <Link :href="batchesIndex()">Cancel</Link>
                    </Button>
                    <Button :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Creating...' : 'Create Batch' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
