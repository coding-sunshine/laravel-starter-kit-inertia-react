import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

interface Props {
    planSlug: string;
    planName: string;
    planPrice: number;
    setupFee: number;
    gateway: string;
}

export default function SignupRegister({ planSlug, planName, planPrice, setupFee, gateway }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        mobile: '',
        business_name: '',
        abn: '',
        plan_slug: planSlug,
        referral_code: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/signup/provision');
    }

    return (
        <AuthLayout
            title={`Sign up — ${planName}`}
            description={`${planPrice > 0 ? `$${planPrice}/mo` : 'Free'}${setupFee > 0 ? ` + $${setupFee} setup` : ''}`}
        >
            <Head title={`Sign Up – ${planName}`} />

            <form onSubmit={handleSubmit} className="space-y-4">
                <input type="hidden" name="plan_slug" value={planSlug} />

                <FormField>
                    <Label htmlFor="name">Full Name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="Jane Smith"
                        required
                        autoFocus
                    />
                    {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                </FormField>

                <FormField>
                    <Label htmlFor="email">Email Address</Label>
                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="jane@example.com"
                        required
                    />
                    {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
                </FormField>

                <FormField>
                    <Label htmlFor="mobile">Mobile Number</Label>
                    <Input
                        id="mobile"
                        type="tel"
                        value={data.mobile}
                        onChange={(e) => setData('mobile', e.target.value)}
                        placeholder="+61 4xx xxx xxx"
                    />
                    {errors.mobile && <p className="text-xs text-destructive">{errors.mobile}</p>}
                </FormField>

                <FormField>
                    <Label htmlFor="business_name">Business Name</Label>
                    <Input
                        id="business_name"
                        value={data.business_name}
                        onChange={(e) => setData('business_name', e.target.value)}
                        placeholder="Smith Real Estate"
                        required
                    />
                    {errors.business_name && <p className="text-xs text-destructive">{errors.business_name}</p>}
                </FormField>

                <FormField>
                    <Label htmlFor="abn">ABN (optional)</Label>
                    <Input
                        id="abn"
                        value={data.abn}
                        onChange={(e) => setData('abn', e.target.value)}
                        placeholder="12 345 678 901"
                    />
                    {errors.abn && <p className="text-xs text-destructive">{errors.abn}</p>}
                </FormField>

                <FormField>
                    <Label htmlFor="referral_code">Referral Code (optional)</Label>
                    <Input
                        id="referral_code"
                        value={data.referral_code}
                        onChange={(e) => setData('referral_code', e.target.value)}
                        placeholder="FRIEND123"
                    />
                    {errors.referral_code && <p className="text-xs text-destructive">{errors.referral_code}</p>}
                </FormField>

                <div className="rounded-lg border bg-muted/50 p-3 text-sm">
                    <p className="font-medium">Payment via {gateway === 'eway' ? 'eWAY' : 'Stripe'}</p>
                    <p className="text-muted-foreground">
                        You will be redirected to complete payment securely after submitting.
                    </p>
                </div>

                <Button
                    type="submit"
                    className="w-full"
                    disabled={processing}
                    data-pan="signup-register-submit"
                >
                    {processing ? (
                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                    ) : null}
                    Create account & continue to payment
                </Button>
            </form>
        </AuthLayout>
    );
}
