<?php

namespace Database\Seeders;

use App\Models\Attendant;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Department;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventType;
use App\Models\Member;
use App\Models\Resource;
use App\Models\ResourceCategory;
use Illuminate\Database\Seeder;

class DagunduroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eventCategories = [
            [
                'name' => 'Special Service',
                'slug' => 'special_service',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Workshops & Retreats',
                'slug' => 'workshops_retreats',
                'is_active' => 'Active'
            ]
        ];

        $eventTypes = [
            [
                'name' => 'Upcoming',
                'slug' => 'upcoming',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Past',
                'slug' => 'past',
                'is_active' => 'Active'
            ]
        ];


        $events = [
            [
                'user_id' => 1,
                'event_category_id' => 1,
                'event_type_id' => 1,
                'title' => 'Embracing Grace and Growth Together Lectureship',
                'address' => 'Cedarwood Retreat Center, 123 Serenity Lane, Hill Country, TX 78676',
                'start_date' => now(),
                'end_date' => now(),
                'start_date_time' => '8AM',
                'end_date_time' => '9PM',
                'banner' => null,
                'description' => 'Join us for a transformative weekend retreat designed to rejuvenate your spirit and deepen your faith. “Renewed Spirits” is an opportunity to step away from the busyness of everyday life and immerse yourself in a peaceful environment where you can focus on personal growth and spiritual renewal.
    
                    Throughout this retreat, you’ll engage in inspiring worship sessions, thought-provoking discussions, and meaningful reflection activities. Experience the power of community as you connect with fellow participants through shared experiences and support each other\'s spiritual journeys.

                    Highlights of the retreat include:
                    - **Soulful Worship:** Engage in uplifting worship that will inspire and refresh your spirit.
                    - **Guided Reflection:** Participate in structured times of personal reflection and group sharing to explore your faith and personal growth.
                    - **Workshops and Seminars:** Attend sessions led by experienced speakers and leaders on topics such as grace, purpose, and spiritual growth.
                    - **Outdoor Activities:** Enjoy nature walks and outdoor gatherings designed to foster a deeper connection with God and with one another.
                    - **Rest and Renewal:** Find peace in moments of solitude and relaxation to recharge and renew your spirit.

                    Whether you’re seeking a deeper connection with God, looking to strengthen your relationships within the church community, or simply in need of a refreshing break, “Renewed Spirits” promises a weekend of growth, grace, and inspiration. Come ready to embrace new perspectives and return home with a renewed sense of purpose and connection.',
                'recurring' => 'true',
                'status' => 'Published',
            ],
        ];

        $attendants = [
            [
                'user_id' => 1,
                'event_id' => 1,
                'name' => 'Tolani Oladipupo',
                'email' => 'Clarence_Dietrich@hotmail.com',
                'country_code' => "+234",
                'phoneno' => "1-484-829-9003",
                'home_address' => '1234 Maple Street, Apt 5B, Springfield, IL 62704',
                'occupation' => 'Software Engineer',
                'status' => 'Active'
            ],
        ];

        $blogCategories = [
            [
                'name' => 'All',
                'slug' => 'all',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Articles',
                'slug' => 'articles',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Testimonials',
                'slug' => 'testimonials',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Church News',
                'slug' => 'church_news',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Updates',
                'slug' => 'updates',
                'is_active' => 'Active'
            ]
        ];

        $blogs = [
            [
                'user_id' => 1,
                'blog_category_id' => 1,
                'title' => 'Our Daily Manna',
                'author_name' => 'Youth Leader',
                'blog_banner' => NULL,
                'author_image' => NULL,
                'description' => 'Office ipsum you must be muted. Muted book journey do site money let just. Work teams light live request finance status globalize welcome. Expectations deliverables masking both design language value-added get. Drawing-board emails deploy talk heads-up first-order ground we\'ve lunch job.',
                'show_author' => 'true',
                'allow_comments' => 'true',
                'allow_share' => 'true',
                'allow_likes' => 'true',
                'status' => 'Posts'
            ],
        ];

        $resourceCategories = [
            [
                'name' => 'Devotional books',
                'slug' => 'devotional_books',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Bible study guides',
                'slug' => 'bible_study_guides',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Music and worship resources',
                'slug' => 'music_and_worship_resources',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Prayer resources',
                'slug' => 'prayer_resources',
                'is_active' => 'Active'
            ]
        ];

        $resources = [
            [
                'user_id' => 1,
                'resource_category_id' => 1,
                'resource_file' => NULL,
                'title' => 'Where are you God?',
                'author' => "Author",
                'file_type' => "PDF",
                'resource_type' => "Document",
                'status' => 'Published'
            ],
        ];

        $members = [
            [
                'full_name' => 'Tolani Oladipupo',
                'email' => 'Clarence_Dietrich@hotmail.com',
                'phone_number' => "1-484-829-9003",
                'message' => "Short message",
                'home_address' => '1234 Maple Street, Apt 5B, Springfield, IL 62704',
                'occupation' => 'Software Engineer',
                'status' => 'Active'
            ],
        ];

        $departments = [
            [
                'name' => 'Music Department',
                'slug' => 'music_department',
                'content' => 'The Music Department at Harvest Point Ministry stands out as a bilingual choir, skillfully ministering in both Yoruba and English to connect with our diverse congregation. Led by Elder Ebenezer Adenipekun, with the support of Deputy Evangelist Yemisi Kolawole, the department is committed to uplifting worship through music. The team is further strengthened by Mrs. Abimbola Ajayi (Secretary), Mrs. Grace Idowu (Treasurer), and Mrs. Ajoke Amara (Finance Secretary).',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Ushering Department',
                'slug' => 'ushering_department',
                'content' => 'The Ushering Department serves as the first point of contact for visitors and members alike. Trained to warmly welcome and assist attendees, they ensure a seamless and organized worship experience. This department is led by Mrs. Hannah Olaribigbe, with Ms. Laide Pearse as Deputy, Mrs. Caroline Adedipe as Secretary, and a dedicated team of financial secretaries and treasurers.',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Sanctuary Keepers Department',
                'slug' => 'sanctuary_keepers_department',
                'content' => 'Responsible for maintaining the cleanliness and sanctity of our worship center, the Sanctuary Keepers Department is led by Deacon Devon Ojo, supported by Deaconess Badero and Secretary Deaconess Ruth Adenipekun. Their diligent work ensures a serene and welcoming environment for worship.',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Protocol Department',
                'slug' => 'protocol_department',
                'content' => 'The Protocol Department oversees the coordination and organization of church activities and events. Minister Adekemi Adenosis heads this department, with Prophet Seun Ajayi as Secretary and Mr. Babatunde Adeyemi as Deputy.',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Men Fellowship',
                'slug' => 'Men_fellowship',
                'content' => 'The Men Fellowship is dedicated to nurturing the spiritual growth and leadership of men within the church. Through tailored teachings and discussions, the fellowship addresses the unique roles and responsibilities of fathers and husbands. Elder Samuel Iletoogun leads the team, supported by Deputy Mr. Kehinde Odukoya, Secretary Mr. Niyi Ojikutu, and Treasurer Mr. Adewale Akinde.',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Women Fellowship',
                'slug' => 'women_fellowship',
                'content' => 'The Women Fellowship, led by Evangelist Clara Aderemi Odeneye and Deputy Evangelist Nancy Akiitan, empowers women through spiritual growth and community support. The team includes Mrs. Caroline Adedipe (Secretary), Mr. Ayo Odukoya (Finance Secretary), and Mrs. Hannah Olaribigbe (Treasurer). Sub-units within the fellowship focus on grassroots outreach, addressing issues that impact women’s welfare.',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Security Department',
                'slug' => 'security_department',
                'content' => 'Ensuring the safety and security of our worship environment is the primary responsibility of the Security Department. Headed by Mr. Adeniyi Adewunmi, with Elder Solomon Adebayo as Deputy, the team includes Mr. John Alofe, Mr. Adewale Akide, Captain Lawal, and Mrs. Ajoke Abi. They oversee the security of the premises, vehicles, and worship center.',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Scripture Reading Department',
                'slug' => 'scripture_reading_department',
                'content' => 'This department, comprised of lay-readers, is responsible for the thoughtful and impactful reading of scriptures during services. Their ministry ensures the Word of God is shared with clarity and reverence.',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Children’s DepartmentChildren’s Department',
                'slug' => 'children’s_department',
                'content' => 'Dedicated to nurturing the spiritual growth of our youngest members, the Children’s Department is led by Evangelist Lara Adewunmi. She is supported by Mrs. Mary Thomas, Minister Darji, Mrs. Akinde, Mrs. Adesola Odukoya, and Mrs. Odun Omolesho. Together, they create a vibrant and engaging environment for children to learn and grow in faith.',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Youth Department',
                'slug' => 'youth_department',
                'content' => 'The Youth Department, under the leadership of Deacon Joel Faremi, focuses on empowering young people to grow in their faith and become active contributors to the church and society.',
                'is_active' => 'Active'
            ],
            [
                'name' => 'Media Department',
                'slug' => 'media_department',
                'content' => 'The Media Department plays a vital role in amplifying the ministry’s message through various digital and traditional platforms. Headed by Deacon Olawale Ajayi, with Deacon Joel Faremi as Deputy, the team includes Mr. Abayomi Tafa, Ms. Koyinsola, Deaconess Ruth Adenipekun, and Miss Caroline Awe. Their efforts ensure that the gospel reaches a wider audience and that church activities are well-documented and shared.',
                'is_active' => 'Active'
            ],
        ];


        dump("Running event category seeder");
        foreach ($eventCategories as $eventCategory) {
            $newEventCategory = EventCategory::where('name', '=', $eventCategory['name'])->first();
            if ($newEventCategory === null) {
                $newEventCategory = EventCategory::create([
                    'name'          => $eventCategory['name'],
                    'slug'          => $eventCategory['slug'],
                    'is_active'          => $eventCategory['is_active']
                ]);
            }
        }
        dump("Event category table seeder ran successfully");


        dump("Running event type table seeder");
        foreach ($eventTypes as $eventType) {
            $neweventType = EventType::where('name', '=', $eventType['name'])->first();
            if ($neweventType === null) {
                $neweventType = EventType::create([
                    'name'          => $eventType['name'],
                    'slug'          => $eventType['slug'],
                    'is_active'          => $eventType['is_active']
                ]);
            }
        }
        dump("Event type table seeder ran successfully");


        dump("Running event table seeder");
        foreach ($events as $event) {
            $newEvent = Event::where('title', '=', $event['title'])->first();
            if ($newEvent === null) {
                $newEvent = Event::create([
                    'user_id'          => $event['user_id'],
                    'event_category_id'          => $event['event_category_id'],
                    'event_type_id'          => $event['event_type_id'],
                    'title'          => $event['title'],
                    'address'          => $event['address'],
                    'start_date'          => $event['start_date'],
                    'end_date'          => $event['end_date'],
                    'start_date_time'          => $event['start_date_time'],
                    'end_date_time'          => $event['end_date_time'],
                    'banner'          => $event['banner'],
                    'description'          => $event['description'],
                    'recurring'          => $event['recurring'],
                    'status'          => $event['status']
                ]);
            }
        }
        dump("Events seeder ran successfully");


        dump("Running attendats table seeder");
        foreach ($attendants as $attendant) {
            $newAttendant = Attendant::where('name', '=', $attendant['name'])->first();
            if ($newAttendant === null) {
                $newAttendant = Attendant::create([
                    'user_id'          => $attendant['user_id'],
                    'event_id'          => $attendant['event_id'],
                    'name'          => $attendant['name'],
                    'email'          => $attendant['email'],
                    'country_code'          => $attendant['country_code'],
                    'phoneno'          => $attendant['phoneno'],
                    'home_address'          => $attendant['home_address'],
                    'occupation'          => $attendant['occupation'],
                    'status'          => $attendant['status']
                ]);
            }
        }
        dump("Attendants seeder ran successfully");

        dump("Running blog category seeder");
        foreach ($blogCategories as $blogCategory) {
            $newBlogCategory = BlogCategory::where('name', '=', $blogCategory['name'])->first();
            if ($newBlogCategory === null) {
                $newBlogCategory = BlogCategory::create([
                    'name'          => $blogCategory['name'],
                    'slug'          => $blogCategory['slug'],
                    'is_active'          => $blogCategory['is_active']
                ]);
            }
        }
        dump("Blog category table seeder ran successfully");

        dump("Running blogs table seeder");
        foreach ($blogs as $blog) {
            $newBlog = Blog::where('title', '=', $blog['title'])->first();
            if ($newBlog === null) {
                $newBlog = Blog::create([
                    'user_id'          => $blog['user_id'],
                    'blog_category_id'          => $blog['blog_category_id'],
                    'title'          => $blog['title'],
                    'author_name'          => $blog['author_name'],
                    'blog_banner'          => $blog['blog_banner'],
                    'author_image'          => $blog['author_image'],
                    'description'          => $blog['description'],
                    'show_author'          => $blog['show_author'],
                    'allow_comments'          => $blog['allow_comments'],
                    'allow_share'          => $blog['allow_share'],
                    'allow_likes'          => $blog['allow_likes'],
                    'status'          => $blog['status']
                ]);
            }
        }
        dump("Blogs seeder ran successfully");

        dump("Running resources category seeder");
        foreach ($resourceCategories as $resourceCategory) {
            $newResourceCategory = ResourceCategory::where('name', '=', $resourceCategory['name'])->first();
            if ($newResourceCategory === null) {
                $newResourceCategory = ResourceCategory::create([
                    'name'          => $resourceCategory['name'],
                    'slug'          => $resourceCategory['slug'],
                    'is_active'          => $resourceCategory['is_active']
                ]);
            }
        }
        dump("Resources category table seeder ran successfully");

        dump("Running resources table seeder");
        foreach ($resources as $resource) {
            $newResource = Resource::where('title', '=', $resource['title'])->first();
            if ($newResource === null) {
                $newResource = Resource::create([
                    'user_id'          => $resource['user_id'],
                    'resource_category_id'          => $resource['resource_category_id'],
                    'resource_file'          => $resource['resource_file'],
                    'title'          => $resource['title'],
                    'author'          => $resource['author'],
                    'file_type'          => $resource['file_type'],
                    'resource_type'          => $resource['resource_type'],
                    'status'          => $resource['status']
                ]);
            }
        }

        dump("Resources seeder ran successfully");

        dump("Running members table seeder");
        foreach ($members as $member) {
            $newMember = Member::where('full_name', '=', $member['full_name'])->first();
            if ($newMember === null) {
                $newMember = Member::create([
                    'full_name'          => $member['full_name'],
                    'email'          => $member['email'],
                    'phone_number'          => $member['phone_number'],
                    'message'          => $member['message'],
                    'home_address'          => $member['home_address'],
                    'occupation'          => $member['occupation'],
                    'status'          => $member['status']
                ]);
            }
        }
        dump("Members seeder ran successfully");

        dump("Running departments seeder");
        foreach ($departments as $department) {
            $newDepartment = Department::where('name', '=', $department['name'])->first();
            if ($newDepartment === null) {
                $newDepartment = Department::create([
                    'name'          => $department['name'],
                    'slug'          => $department['slug'],
                    'content'          => $department['content'],
                    'is_active'          => $department['is_active']
                ]);
            }
        }
        dump("Departments table seeder ran successfully");
    }
}
