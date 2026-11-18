import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status, membership, directoryVisible, shareContactInfo }) {
    const membershipForm = useForm({
        address: membership?.address || '',
        mobile: membership?.mobile || '',
        whatsapp: membership?.whatsapp || '',
        personal_email: membership?.personal_email || '',
        occupation: membership?.occupation || '',
        business_name: membership?.business_name || '',
        directory_visible: directoryVisible,
        show_contact_info: shareContactInfo,
    });

    const updateMembership = (event) => {
        event.preventDefault();
        membershipForm.patch(route('profile.membership.update'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Profile
                </h2>
            }
        >
            <Head title="Profile" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    {membership && (
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                            <form onSubmit={updateMembership} className="max-w-xl space-y-5">
                                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">Membership details</h2>
                                {[
                                    ['address', 'Address'],
                                    ['mobile', 'Mobile'],
                                    ['whatsapp', 'WhatsApp'],
                                    ['personal_email', 'Personal email'],
                                    ['occupation', 'Occupation'],
                                    ['business_name', 'Business name'],
                                ].map(([key, label]) => (
                                    <label key={key} className="block text-sm text-gray-700 dark:text-gray-200">
                                        {label}
                                        <input
                                            type={key === 'personal_email' ? 'email' : 'text'}
                                            value={membershipForm.data[key]}
                                            onChange={(event) => membershipForm.setData(key, event.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300"
                                        />
                                        {membershipForm.errors[key] && <span className="text-red-600">{membershipForm.errors[key]}</span>}
                                    </label>
                                ))}
                                <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" checked={membershipForm.data.directory_visible}
                                        onChange={(event) => membershipForm.setData('directory_visible', event.target.checked)} />
                                    Show my profile in the private member directory
                                </label>
                                <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="checkbox" checked={membershipForm.data.show_contact_info}
                                        onChange={(event) => membershipForm.setData('show_contact_info', event.target.checked)} />
                                    Share my email and mobile number with other active members
                                </label>
                                <button type="submit" disabled={membershipForm.processing}
                                    className="rounded-md bg-slfa-primary px-4 py-2 font-semibold text-white disabled:opacity-50">Save membership details</button>
                                {membershipForm.recentlySuccessful && <span className="text-sm text-green-700">Saved.</span>}
                            </form>
                        </div>
                    )}

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
