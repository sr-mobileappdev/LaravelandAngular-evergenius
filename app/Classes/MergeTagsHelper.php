<?php
namespace App\Classes;

class MergeTagsHelper
{

    public static function RenderSmsBroadCast($message, $contactDetails, $companyDetails)
    {
       
        $contact_merge_tags = mergeTagsContact();
        $company_merge_tags = mergeTagsCompany();
        
        //Render Contact Tags
        foreach ($contact_merge_tags as $key => $mTag) {
            $message = str_replace($key, $contactDetails[$mTag], $message);
        }

        //Render Company Tags
        foreach ($company_merge_tags as $ckey => $cmTag) {
            $message = str_replace($ckey, $companyDetails[$cmTag], $message);
        }

        return $message;
    }

    public static function RenderLeadsTags($message, $leadData, $companyDetails)
    {
        $company_merge_tags = mergeTagsCompany();
        $lead_merge_tags = mergeTagsLead();
        foreach ($lead_merge_tags as $key => $mTag) {
            $message = str_replace($key, $leadData[$mTag], $message);
        }
        foreach ($company_merge_tags as $ckey => $cmTag) {
            $message = str_replace($ckey, $companyDetails[$cmTag], $message);
        }
        return $message;
    }
}
