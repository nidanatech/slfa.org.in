import SLFAWebsiteLayout from '@/Layouts/SLFAWebsiteLayout';
import SLFAPageHero from '@/Components/SLFAPageHero';
import { Head, useForm, usePage } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status, membership, directoryVisible, shareContactInfo }) {
    const { auth } = usePage().props;
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
        <SLFAWebsiteLayout user={auth.user} darkNav={true}>
            <Head title="Profile" />
            <SLFAPageHero eyebrow="Your account" title={<>Your <span className="italic font-light text-[#D4AF37]">profile</span></>} description="Keep your account details current and choose what you share with the member community." />

            <section className="bg-[#f5f1e9] py-16 sm:py-20">
                <div className="mx-auto max-w-5xl space-y-12 px-4 sm:px-6 lg:px-8">
                    <div className="border-t border-[#b7a676] pt-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    {membership && (
                        <div className="border-t border-[#b7a676] pt-8">
                            <form onSubmit={updateMembership} className="max-w-xl space-y-5">
                                <h2 className="font-abhaya text-2xl font-bold text-gray-950">Membership details</h2>
                                <p className="text-sm text-gray-600">Update your details and choose your directory visibility.</p>
                                {[
                                    ['address', 'Address'],
                                    ['mobile', 'Mobile'],
                                    ['whatsapp', 'WhatsApp'],
                                    ['personal_email', 'Personal email'],
                                    ['occupation', 'Occupation'],
                                    ['business_name', 'Business name'],
                                ].map(([key, label]) => (
                                    <label key={key} className="block text-sm font-medium text-gray-700">
                                        {label}
                                        <input
                                            type={key === 'personal_email' ? 'email' : 'text'}
                                            value={membershipForm.data[key]}
                                            onChange={(event) => membershipForm.setData(key, event.target.value)}
                                            className="mt-1 block w-full rounded-none border-[#cbbb93] bg-white/70 focus:border-[#800000] focus:ring-[#800000]"
                                        />
                                        {membershipForm.errors[key] && <span className="text-red-600">{membershipForm.errors[key]}</span>}
                                    </label>
                                ))}
                                <label className="flex items-center gap-2 border-t border-[#d5c8a6] py-4 text-sm text-gray-700">
                                    <input type="checkbox" checked={membershipForm.data.directory_visible}
                                        onChange={(event) => membershipForm.setData('directory_visible', event.target.checked)} />
                                    Show my profile in the private member directory
                                </label>
                                <label className="flex items-center gap-2 border-t border-[#d5c8a6] py-4 text-sm text-gray-700">
                                    <input type="checkbox" checked={membershipForm.data.show_contact_info}
                                        onChange={(event) => membershipForm.setData('show_contact_info', event.target.checked)} />
                                    Share my email and mobile number with other active members
                                </label>
                                <button type="submit" disabled={membershipForm.processing}
                                    className="rounded-full bg-gray-950 px-6 py-3 font-semibold text-white hover:bg-[#800000] disabled:opacity-50">Save membership details</button>
                                {membershipForm.recentlySuccessful && <span className="text-sm text-green-700">Saved.</span>}
                            </form>
                        </div>
                    )}

                    <div className="border-t border-[#b7a676] pt-8">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    <div className="border-t border-[#b7a676] pt-8">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </section>
        </SLFAWebsiteLayout>
    );
}
