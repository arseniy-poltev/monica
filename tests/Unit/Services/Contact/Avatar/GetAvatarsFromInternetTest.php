<?php

namespace Tests\Unit\Services\Contact\Avatar;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Avatar\GetAvatarsFromInternet;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GetAvatarsFromInternetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_a_contact_object_with_avatars()
    {
        $contact = factory(Contact::class)->create([]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $contact->account->id,
        ]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'matt@wordpress.com',
        ]);

        $request = [
            'contact_id' => $contact->id,
        ];

        $contact = (new GetAvatarsFromInternet)->execute($request);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );

        $this->assertNotNull(
            $contact->avatar_adorable_url
        );

        $this->assertNotNull(
            $contact->avatar_gravatar_url
        );
    }

    public function test_gravatar_is_null_if_contact_doesnt_have_an_email()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
        ];

        $contact = (new GetAvatarsFromInternet)->execute($request);

        $this->assertNull(
            $contact->avatar_gravatar_url
        );
    }

    public function test_avatar_source_is_reset_and_set_to_adorable_if_gravatar_doesnt_exist_anymore()
    {
        $contact = factory(Contact::class)->create([
            'avatar_source' => 'gravatar',
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $contact->account->id,
        ]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'matt@wordpress.com',
        ]);

        $request = [
            'contact_id' => $contact->id,
        ];

        $contact = (new GetAvatarsFromInternet)->execute($request);

        // now we call the service again to reset the gravatar url
        $contactField->delete();
        $contact = (new GetAvatarsFromInternet)->execute($request);

        $this->assertNull(
            $contact->avatar_gravatar_url
        );

        $this->assertEquals(
            'adorable',
            $contact->avatar_source
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'size' => 200,
        ];

        $this->expectException(ValidationException::class);
        (new GetAvatarsFromInternet)->execute($request);
    }
}
