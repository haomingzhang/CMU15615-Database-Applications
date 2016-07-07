#include "def.h"
extern FILE *fpbtree;
extern int sqCount; /* statistics: # of successf. queries asked */
extern int uqCount; /* # of unsuccessf. queries */

//extern int iscommon(char *word);
extern int check_word(char *word);
extern int strtolow(char *s);
extern int getpostings(POSTINGSPTR pptr);


extern int FreePage(struct PageHdr *PagePtr);
extern struct PageHdr *FetchPage(PAGENO Page);

int ComparePrefix_Right(char *Key, char *Word) {
    
    int m = max(strlen(Key), strlen(Word));
    
    int i = 0;
    for (i = 0; i < m; i++) {
        if (i == strlen(Key) || i == strlen(Word)) {//same
            return (2);
        } else if (Key[i] < Word[i]) {
            return (1);
        } else if (Key[i] > Word[i]) {
            return (2);
        }
    }
    return (2);
}

/* KeyListTraverser: Pointer to the list of keys */
/* Key: The new possible key */
/* Found: report result */
int FindInsertionPosition_Right(struct KeyRecord *KeyListTraverser, char *Key,
                                int *last, NUMKEYS NumKeys, int Count) {
    int Result;
    int ComparePrefix_Right(char *Key, char *Word);
    
    /* -christos- the next block probably provides for
     insertion in empty list (useful for insertion in root
     for the first time! */
    
    if (NumKeys == 0) {
        *last = TRUE;
        return (Count);
    }
    
    /* Compare the the possible new key with the key stored in B-Tree */
    Result = ComparePrefix_Right(Key, KeyListTraverser->StoredKey);
    
    NumKeys = NumKeys - 1;
    Count = Count + 1;
    
    
    
    if (NumKeys > 0) {
        if (Result == 1)        /* New key < stored key */
            return (Count - 1); /* Location before stored key */
        else                    /* New key > stored key: keep searching */
        {
            KeyListTraverser = KeyListTraverser->Next;
            return (FindInsertionPosition_Right(KeyListTraverser, Key, last, NumKeys,
                                                Count));
        }
    } else /* this is the last key in the list -- search must terminate */
    {
        if (Result == 1)        /* New key < stored key */
            return (Count - 1); /* Location before stored key */
        else{
            *last = TRUE;
            return (Count); /* New key will be the last key */
        }
    }
}



PAGENO FindPageNumOfChild_Right(struct PageHdr *PagePtr,
                                struct KeyRecord *KeyListTraverser, char *Key,
                                NUMKEYS NumKeys)

/* A pointer to the list of keys */

/* Possible new key */
{
    /* Auxiliary Definitions */
    int Result;
    char *Word; /* Key stored in B-Tree */
    int ComparePrefix_Right(char *Key, char *Word);
    
    /* Compare the possible new key with key stored in B-Tree */
    Word = KeyListTraverser->StoredKey;
    (*(Word + KeyListTraverser->KeyLen)) = '\0';
    Result = ComparePrefix_Right(Key, Word);
    
    NumKeys = NumKeys - 1;
    
    if (NumKeys > 0) {
        if (Result == 2) { /* New key > stored key:  keep searching */
            KeyListTraverser = KeyListTraverser->Next;
            return (
                    FindPageNumOfChild_Right(PagePtr, KeyListTraverser, Key, NumKeys));
        } else                                /* New key <= stored key */
            return (KeyListTraverser->PgNum); /* return left child */
    } else /* This is the last key in this page */
    {
        if (Result == 1)    /* New key <= stored key */
            return (KeyListTraverser->PgNum);  /* return left child */
        else                                   /* New key > stored key */
            return (PagePtr->PtrToFinalRtgPg); /* return rightmost child */
    }
}

POSTINGSPTR searchLeaf_Right(struct PageHdr *PagePtr, char *key, char *resultkey) {
    
    struct KeyRecord *KeyListTraverser;
    int InsertionPosition; /* Position for insertion */
    int FindInsertionPosition_Right(struct KeyRecord * KeyListTraverser, char *Key,
                                    int *last, NUMKEYS NumKeys, int Count);
    int Count, last, i;
    last = FALSE;
    Count = 0;
    
    /* Find insertion position */
    KeyListTraverser = PagePtr->KeyListPtr;
    InsertionPosition = FindInsertionPosition_Right(KeyListTraverser, key, &last,
                                                    PagePtr->NumKeys, Count);
    //printf("Inserpos: %d\n",InsertionPosition);
    /* key is the last in the list */
    if (last == TRUE) {
        //printf("last in the list\n");
        if (PagePtr->PgNumOfNxtLfPg == NULLPAGENO){
            
            return(NONEXISTENT);
        }else{
            
            struct PageHdr * p = FetchPage(PagePtr->PgNumOfNxtLfPg);
            strcpy(resultkey, p->KeyListPtr->StoredKey);
            return (p->KeyListPtr->Posting);
        }
    } else {
        for (i = 0; i < InsertionPosition; i++)
            KeyListTraverser = KeyListTraverser->Next;
        
        strcpy(resultkey, KeyListTraverser->StoredKey);
        return (KeyListTraverser->Posting);
    }
}


/**
 * recursive call to find the page in which the key should reside
 * and return the page number (guaranteed to be a leaf page).
 */
PAGENO treesearch_page_Right(PAGENO PageNo, char *key) {
    PAGENO result;
    struct PageHdr *PagePtr = FetchPage(PageNo);
    if (IsLeaf(PagePtr)) { /* found leaf */
        result = PageNo;
    } else if ((IsNonLeaf(PagePtr)) && (PagePtr->NumKeys == 0)) {
        /* keys, if any, will be stored in Page# 2
         THESE PIECE OF CODE SHOULD GO soon! **/
        result = treesearch_page_Right(FIRSTLEAFPG, key);
    } else if ((IsNonLeaf(PagePtr)) && (PagePtr->NumKeys > 0)) {
        PAGENO ChildPage = FindPageNumOfChild_Right(PagePtr, PagePtr->KeyListPtr, key,
                                                    PagePtr->NumKeys);
        result = treesearch_page_Right(ChildPage, key);
    } else {
        assert(0 && "this should never happen");
    }
    FreePage(PagePtr);
    return result;
}

/**
 * find the posting pointer to which the key should reside, given the
 * starting page number to look at.
 *
 * to search the whole tree, pass in ROOT as the page number.
 */
POSTINGSPTR treesearch_Right(PAGENO PageNo, char *key, char *resultkey) {
    /* recursive call to find page number */
    const PAGENO page = treesearch_page_Right(PageNo, key);
    /* from page number we traverse the leaf page */
    struct PageHdr *PagePtr = FetchPage(page);
    POSTINGSPTR result = searchLeaf_Right(PagePtr, key, resultkey);
    FreePage(PagePtr);
    return result;
}








void get_rightbracket(char *key, char *resultkey) {
    POSTINGSPTR pptr;
    
    /* Print an error message if strlen(key) > MAXWORDSIZE */
    if (strlen(key) > MAXWORDSIZE) {
        //printf("ERROR in \"search\":  Length of key Exceeds Maximum Allowed\n");
        strcpy(resultkey, "*NONE*");
        return;
    }
    /*
    if (iscommon(key)) {
        printf("\"%s\" is a common word - no searching is done\n", key);
        return;
    }
     */
    if (check_word(key) == FALSE) {
        strcpy(resultkey, "*NONE*");
        return;
    }
    /* turn to lower case, for uniformity */
    strtolow(key);
    
    pptr = treesearch_Right(ROOT, key, resultkey);
    if (pptr == NONEXISTENT) {
        strcpy(resultkey, "*NONE*");
        uqCount++;
    } else {
        ;
    }
}

