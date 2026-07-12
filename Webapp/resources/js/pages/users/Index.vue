<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Search, Trash2, UserCog, UserPlus } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';
import type { Auth, BreadcrumbItem } from '@/types';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: string;
    must_change_password: boolean;
    created_at: string;
    updated_at: string;
};

type RoleOption = {
    value: string;
    label: string;
};

interface Props {
    users: ManagedUser[];
    roles: RoleOption[];
}

defineOptions({
    layout: [],
});

const props = defineProps<Props>();
const page = usePage<{ auth: Auth }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'User Management',
        href: '/users',
    },
];

const search = ref('');
const isDialogOpen = ref(false);
const editingUser = ref<ManagedUser | null>(null);

const form = useForm({
    name: '',
    email: '',
    role: 'supervisor',
    password: '',
});

const filteredUsers = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (term === '') {
        return props.users;
    }

    return props.users.filter((user) =>
        [user.name, user.email, user.role].some((value) =>
            value.toLowerCase().includes(term),
        ),
    );
});

const roleLabel = (role: string) =>
    props.roles.find((option) => option.value === role)?.label ?? role;

const roleVariant = (role: string) => {
    if (role === 'admin') {
        return 'default';
    }

    if (role === 'supervisor') {
        return 'secondary';
    }

    return 'outline';
};

const formatDate = (dateString: string) =>
    new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

const resetForm = () => {
    form.clearErrors();
    form.reset();
    form.role = 'supervisor';
};

const openCreateDialog = () => {
    editingUser.value = null;
    resetForm();
    isDialogOpen.value = true;
};

const openEditDialog = (user: ManagedUser) => {
    editingUser.value = user;
    resetForm();
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.password = '';
    isDialogOpen.value = true;
};

const submitForm = () => {
    if (editingUser.value) {
        form.patch(`/users/${editingUser.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
                resetForm();
            },
        });

        return;
    }

    form.post('/users', {
        preserveScroll: true,
        onSuccess: () => {
            isDialogOpen.value = false;
            resetForm();
        },
    });
};

const deleteUser = (user: ManagedUser) => {
    if (user.id === page.props.auth.user.id) {
        return;
    }

    if (confirm(`Delete ${user.name}?`)) {
        router.delete(`/users/${user.id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">
                        User Management
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Manage staff accounts and system access roles.
                    </p>
                </div>

                <Button @click="openCreateDialog">
                    <UserPlus class="mr-2 h-4 w-4" />
                    Add User
                </Button>
            </div>

            <div class="relative w-full max-w-sm">
                <Search
                    class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search users..."
                    class="pl-8"
                />
            </div>

            <div
                class="overflow-hidden rounded-lg border bg-card text-card-foreground"
            >
                <Table>
                    <TableHeader class="bg-muted/40">
                        <TableRow class="hover:bg-transparent">
                            <TableHead class="pl-4">User</TableHead>
                            <TableHead class="w-[190px]">Role</TableHead>
                            <TableHead class="w-[180px]">
                                Password Status
                            </TableHead>
                            <TableHead class="w-[150px]">Created</TableHead>
                            <TableHead class="w-[140px] pr-4 text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in filteredUsers" :key="user.id">
                            <TableCell class="pl-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary text-xs font-semibold uppercase"
                                    >
                                        {{ user.name.substring(0, 2) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">
                                            {{ user.name }}
                                        </p>
                                        <p
                                            class="truncate text-sm text-muted-foreground"
                                        >
                                            {{ user.email }}
                                        </p>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    :variant="roleVariant(user.role)"
                                    class="px-2 py-0.5 text-[11px] font-medium"
                                >
                                    {{ roleLabel(user.role) }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <span
                                    v-if="user.must_change_password"
                                    class="text-sm text-amber-600 dark:text-amber-400"
                                >
                                    Must change
                                </span>
                                <span
                                    v-else
                                    class="text-sm text-muted-foreground"
                                >
                                    Active
                                </span>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ formatDate(user.created_at) }}
                            </TableCell>
                            <TableCell class="pr-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        class="h-8 w-8"
                                        @click="openEditDialog(user)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        class="h-8 w-8 text-destructive hover:text-destructive"
                                        :disabled="
                                            user.id === page.props.auth.user.id
                                        "
                                        @click="deleteUser(user)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableEmpty
                            v-if="filteredUsers.length === 0"
                            :colspan="5"
                        >
                            <div
                                class="flex flex-col items-center gap-2 text-center"
                            >
                                <UserCog
                                    class="h-8 w-8 text-muted-foreground/60"
                                />
                                <div>
                                    <p class="font-medium">No users found</p>
                                    <p class="text-sm text-muted-foreground">
                                        Add staff accounts for the admin,
                                        supervisor, HR, manager, or center
                                        director roles.
                                    </p>
                                </div>
                            </div>
                        </TableEmpty>
                    </TableBody>
                </Table>
            </div>
        </div>

        <Dialog v-model:open="isDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ editingUser ? 'Edit User' : 'Add User' }}
                    </DialogTitle>
                    <DialogDescription>
                        Intern accounts are managed from the batch dashboard.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitForm">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            autocomplete="name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="role">Role</Label>
                        <Select v-model="form.role">
                            <SelectTrigger id="role">
                                <SelectValue placeholder="Select role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="role in roles"
                                    :key="role.value"
                                    :value="role.value"
                                >
                                    {{ role.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.role" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">
                            {{
                                editingUser
                                    ? 'New Password'
                                    : 'Temporary Password'
                            }}
                        </Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            :placeholder="
                                editingUser
                                    ? 'Leave blank to keep current password'
                                    : ''
                            "
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isDialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ editingUser ? 'Save Changes' : 'Create User' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
